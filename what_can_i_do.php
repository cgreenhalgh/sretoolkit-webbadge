<?php 
/*
 * Experiment with suggesting what the current user can do.
 */
define('WP_USE_THEMES', true);
require_once( dirname(__FILE__) . '/../../../wp-load.php' );
require_once( dirname(__FILE__) . '/functions.php' );

// from WP->main
GLOBAL $wp;
$wp->init();

// from WP->main
//$wp->parse_request($query_args);
$wp->send_headers();
//$wp->query_posts();
//$wp->handle_404();
$wp->register_globals();
do_action_ref_array('wp', array(&$this));

// logged in?!
if (!is_user_logged_in()) {
	get_header(); 
?>
	<div id="primary" class="site-content">
		<div id="content" role="main">
			<article id="post-0">
				<header class="entry-header">
					<h1 class="entry-title">What can I do?</h1>
				</header>
				<div class="entry-content">
					<p>Log in...</p>
					<?php wp_login_form(); ?>
				</div>
			</article>
		</div>
	</div>	
<?php 
	get_footer();
	return;
}

$types = '';
//$types .= "resource(null,null,null,null,null,null).\n";
$types .= "discontiguous(resource/6).\n";
//$types .= "partof(null,null).\n";
$types .= "discontiguous(partof/2).\n";

$current_user = wp_get_current_user();
$facts = '';
$account_atom = "user".$current_user->ID;
$service_atom = "srebadges0";
$facts .= "% account id,service id,service type,login,url\n";
$facts .= "account(".$account_atom.",".$service_atom.",service_srebadges,'".esc_attr($current_user->user_login)."','".site_url()."').\n";

// schemes...
$scheme_query = new WP_Query( array( 'post_type' => 'sretk_scheme', 'author' => $current_user->ID, 
		'post_status' => array( 'publish', 'pending', 'draft' ) ) );
while($scheme_query->have_posts() ) {
	$scheme_query->next_post();
	// $scheme_query->post...
	$scheme_atom = "scheme".$scheme_query->post->ID;

	$published = $scheme_query->post->post_status=='publish';
	$facts .= "% resource id, resource type, resource title, service id, account id, resource state[published|unpublished]\n";
	$facts .= "resource(".$scheme_atom.",resource_srescheme,'".esc_attr($scheme_query->post->post_title)."',".$service_atom.",".$account_atom.",".($published ? "published" : "unpublished").").\n";
	
	// scheme member(s)
	$member_args = array(
			'post_type' => 'sretk_scheme_member',
			'meta_query' => array(
					array(
							'key' => '_sretk_scheme_id',
							'value' => $scheme_query->post->ID
					)
			),
			'post_status' => array( 'publish', 'pending', 'draft' )
	);
	$member_query = new WP_Query( $member_args );
	while ($member_query->have_posts() ) {
		$member_query->next_post();
		
		$member_atom = "member".$member_query->post->ID;
		$published = $member_query->post->post_status=='publish';
		$current = scheme_mstate_is_current($member_query->post->ID);
		$facts .= "resource(".$member_atom.",resource_sreschememember,'".esc_attr($member_query->post->post_title)."',".$service_atom.",".$account_atom.",".($published ? "published" : "unpublished")."_".($current ? "current" : "notcurrent").").\n";
		$facts .= "partof(".$member_atom.",".$scheme_atom.").\n";
	}	
}

// rules - service-specific
$rules = '';
$rules .= <<<'EOT'
possibleaction1(scheme_add,L,U,null,'',null,'') :- account(A,S,service_srebadges,L,U).
possibleaction1(scheme_publish,L,U,R,RT,null,'') :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,unpublished).
possibleaction1(scheme_edit,L,U,R,RT,null,'') :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,_).
possibleaction1(scheme_member_add,L,U,R,RT,null,'') :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,published).
possibleaction1(scheme_member_edit,L,U,R,RT,R2,RT2) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,_), partof(R,R2), resource(R2,resource_srescheme,RT2,S,A,_).
possibleaction1(scheme_member_makecurrent,L,U,R,RT,R2,RT2) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,published_notcurrent), partof(R,R2), resource(R2,resource_srescheme,RT2,S,A,_).
possibleaction1(scheme_member_makecurrent,L,U,R,RT,R2,RT2) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,unpublished_notcurrent), partof(R,R2), resource(R2,resource_srescheme,RT2,S,A,_).
possibleaction1(scheme_member_publish,L,U,R,RT,R2,RT2) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,unpublished_current), partof(R,R2), resource(R2,resource_srescheme,RT2,S,A,_).
EOT;

// goal(s)
$goal = "possibleaction1(A,L,U,R,RT,R2,RT2)";

// run gprolog...
$tmpfname = tempnam(sys_get_temp_dir(), "sre");
$tmpfname1 = $tmpfname.".pl";
$handle = fopen($tmpfname1, "w");
fwrite($handle, $types);
fwrite($handle, $facts);
fwrite($handle, $rules);
fclose($handle);

$tmpfname2 = tempnam(sys_get_temp_dir(), "sre");
$handle = fopen($tmpfname2, "w");
fwrite($handle, "['".$tmpfname."'].\n");
fwrite($handle, "leash(none).\n");
fwrite($handle, "trace,findall(".$goal.",".$goal.",List).\n");
fclose($handle);

$output = array();
$rval = 0;
$cmd = 'PATH=/lhome/cmg/gprolog-1.4.4/bin:$PATH;'."gprolog 2>&1 < ".$tmpfname2;
$ret = exec($cmd, $output, $rval);

$traces = array();
$list = null;

function parse_list(&$toks) {
	$list = array( 'type' => 'list', 'items' => array() );
	while (count($toks)>0) {
		$tok = $toks[0];
		if ($tok==')')
			break;
		$term = parse_item($toks);
		$list['items'][] = $term;
		if ($term['type']=='error') {
			break;
		}
		$tok = count($toks)>0 ? $toks[0] : null;
		if ($tok!=',')
			break;
		array_shift($toks);
	}
	return $list;
}
function parse_item(&$toks) {
	$tok = array_shift($toks);
	$term = null;
	if ($tok && preg_match('/^[A-Za-z]/', $tok)) {
		$term = array( 'type' => 'atom', 'value' => $tok );
	} else if ($tok && substr($tok, 0, 1)=="'") {
		$term = array( 'type' => 'atom', 'value' => substr($tok,1, -1) );		
	}
	else return array( 'type' => 'error', 'value' => $tok, 'message' => 'expected an item' );
	if (count($toks)==0)
		return $term;
	$tok = $toks[0];
	if ($tok=='(') {
		array_shift($toks);
		$term['type'] = 'predicate';
		$list = parse_list($toks);
		if ($list['type']=='list')
			$term['fields'] = $list['items'];
		else
			$term['fields'] = $list;
		$tok = array_shift($toks);
		if ($tok!=')')
			return array( 'type' => 'error', 'value' => $tok, 'message' => 'expected a )' );
	} 
	return $term;
}
function parse_prolog($s) {
// 	$toks = array();
	preg_match_all('/([A-Za-z][A-Za-z0-9_]*)|[0-9]+|([\']([^\'\\]|\\\\[\'])*[\'])|./', $s, $matches);
	$toks = $matches[0];
	$list = parse_list($toks);
	if (count($toks)>0) {
		$list['items'][] = array( 'type' => 'error', 'value' => $toks, 'message' => 'trailing tokens' );
	}
	return $list;
}

$out = '';
foreach ($output as $o) {
	$out .= $o."\n";
	$matches = array();
	if (preg_match('/^List = [\\[](.*)[\\]]$/', $o, $matches))
		$list = $matches[1];
	else if (preg_match('/^[ \\t]*([0-9]+)[ \\t]+([0-9]+)[ \\t]+([A-Za-z]+):[ \\t]*(.*)$/', $o, $matches)) {
		$t = array(
				'n1' => (int)($matches[1]),
				'n2' => (int)($matches[2]),
				'action' => $matches[3],
				'info' => $matches[4],
				);		
		$traces[] = $t;
	}
}

/* 
 * e.g. 
 * GNU Prolog 1.3.1
By Daniel Diaz
Copyright (C) 1999-2009 Daniel Diaz
compiling /tmp/sreJhp9m8.pl for byte code...
/tmp/sreJhp9m8.pl:7: warning: singleton variables [A,S] for possibleaction1/4
/tmp/sreJhp9m8.pl:8: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl:9: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl:10: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl:11: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl:12: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl:13: warning: singleton variables [RT] for possibleaction1/4
/tmp/sreJhp9m8.pl compiled, 12 lines read - 5216 bytes written, 11 ms

yes
No leashing

yes
The debugger will first creep -- showing everything (trace)
      1    1  Call: findall(_16,possibleaction1(_16,_17,_18,_19),_25)
      2    2  Call: possibleaction1(_16,_17,_18,_19)
      3    3  Call: account(_112,_113,service_srebadges,_17,_18)
      3    3  Exit: account(user2,srebadges0,service_srebadges,cmg,'http://localhost:8180/wp1')
      2    2  Exit: possibleaction1(scheme_add,cmg,'http://localhost:8180/wp1',null)
...
      2    2  Exit: possibleaction1(scheme_member_add,cmg,'http://localhost:8180/wp1',scheme76)
...
      2    2  Fail: possibleaction1(_16,_17,_18,_19)
      1    1  Exit: findall(_16,possibleaction1(_16,_17,_18,_19),[scheme_add,scheme_unpublish,scheme_member_add])

List = [scheme_add,scheme_unpublish,scheme_member_add]

yes
{trace}

 */

// from wp-blog-header
//require_once( dirname(__FILE__) . '/single-sretk_scheme.php' );
//require_once( ABSPATH . WPINC . '/template-loader.php' );
get_header(); ?>

	<div id="primary" class="site-content">
		<div id="content" role="main">

			<article id="post-0">
				<header class="entry-header">
					<h1 class="entry-title">What can I do?</h1>
				</header>

				<div class="entry-content">
				
<?php
$plist = parse_prolog($list);
//print_r($plist);
foreach ($plist['items'] as $actioninfo) {
	echo '<div>';
	if ($actioninfo['type']=='predicate' && $actioninfo['value']=='possibleaction1' && count($actioninfo['fields'])==7) {
		$action = $actioninfo['fields'][0]['value'];
		$login = $actioninfo['fields'][1]['value'];
		$url = $actioninfo['fields'][2]['value'];
		$resource = $actioninfo['fields'][3]['value'];
		$resourcetitle = $actioninfo['fields'][4]['value'];
		$resource2 = $actioninfo['fields'][5]['value'];
		$resourcetitle2 = $actioninfo['fields'][6]['value'];
		if ($action=='scheme_add') {
			$path = "/wp-admin/post-new.php?post_type=sretk_scheme";
			echo '<p><a href="'.esc_attr($url.$path).'">Add a new scheme</a></p>';
		}
		else if ($action=='scheme_publish' && substr($resource, 0, 6)=='scheme') {
			$path = "/wp-admin/post.php?post=".substr($resource, 6)."&action=edit";
			echo '<p><a href="'.esc_attr($url.$path).'">Publish draft scheme '.esc_html($resourcetitle).'</a></p>';
		} 
		else if ($action=='scheme_edit' && substr($resource, 0, 6)=='scheme') {
			$path = "/wp-admin/post.php?post=".substr($resource, 6)."&action=edit";
			echo '<p><a href="'.esc_attr($url.$path).'">Edit scheme "'.esc_html($resourcetitle).'"</a></p>';
		} 
		else if ($action=='scheme_member_add' && substr($resource, 0, 6)=='scheme') {
			// TODO what about initialising the fields??  i.e. Scheme
			$path = "/wp-admin/post-new.php?post_type=sretk_scheme_member";
			echo '<p><a href="'.esc_attr($url.$path).'">Add a member to scheme "'.esc_html($resourcetitle).'"</a></p>';
		} 
		else if ($action=='scheme_member_edit' && substr($resource, 0, 6)=='member') {
			$path = "/wp-admin/post.php?post=".substr($resource, 6)."&action=edit";
			// TODO what about scheme title
			echo '<p><a href="'.esc_attr($url.$path).'">Edit scheme "'.esc_html($resourcetitle2).'" member "'.esc_html($resourcetitle).'"</a></p>';
		} 
		else if ($action=='scheme_member_publish' && substr($resource, 0, 6)=='member') {
			$path = "/wp-admin/post.php?post=".substr($resource, 6)."&action=edit";
			// TODO what about scheme title
			echo '<p><a href="'.esc_attr($url.$path).'">Publish scheme "'.esc_html($resourcetitle2).'" draft member "'.esc_html($resourcetitle).'"</a></p>';
		} 
		else if ($action=='scheme_member_makecurrent' && substr($resource, 0, 6)=='member') {
			$path = "/wp-admin/post.php?post=".substr($resource, 6)."&action=edit";
			// TODO what about scheme title
			echo '<p><a href="'.esc_attr($url.$path).'">Make scheme "'.esc_html($resourcetitle2).'" member "'.esc_html($resourcetitle).'" current</a></p>';
		} 
		else
			///wp-admin/post-new.php?post_type=sretk_scheme_member
			echo '<p>'.$action.'</p>';
	}
	else {
		echo '<p>'; print_r($actioninfo); echo '</p>';
	}
	echo '</div>';
}
?>					
				
					<div>
						<p>Facts:</p>
						<pre><?php echo esc_html($facts); ?></pre>
					</div>
					<div>
						<p>Rules:</p>
						<pre><?php echo esc_html($rules); ?></pre>
					</div>
					<div>
						<p>Command: <?php echo $cmd; ?></p>
						<p>Output (code <?php echo $rval." - ".$ret; ?>):</p>
						<pre><?php echo esc_html($out); ?></pre>
<?php /* $ret = passthru($cmd, $rval); */ ?>
						</div>
						<p>List:</p>
						<pre><?php echo $list; ?></pre>
						<p>Trace:</p>
						<pre><?php 
						foreach ($traces as $t) {
							echo $t['n1']." ".$t['n2']." ".$t['action']." ".$t['info']."\n"; 
						}
						?></pre>
						...?
				</div><!-- .entry-content -->
			</article><!-- #post-0 -->

		</div><!-- #content -->
	</div><!-- #primary -->

<?php 

unlink($tmpfname);
unlink($tmpfname1);
unlink($tmpfname2);

get_footer(); ?>
