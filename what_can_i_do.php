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

$current_user = wp_get_current_user();
$facts = '';
$account_atom = "user".$current_user->ID;
$service_atom = "srebadges0";
$facts .= "% account id,service id,service type,login,url\n";
$facts .= "account(".$account_atom.",".$service_atom.",service_srebadges,'".esc_attr($current_user->user_login)."','".site_url()."').\n";

// schemes...
$scheme_query = new WP_Query( array( 'post_type' => 'sretk_scheme', 'author' => $current_user->ID ) );
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
			)
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
possibleaction1(scheme_add,L,U,null) :- account(A,S,service_srebadges,L,U).
possibleaction1(scheme_publish,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,unpublished).
possibleaction1(scheme_unpublish,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,published).
possibleaction1(scheme_member_add,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_srescheme,RT,S,A,published).
possibleaction1(scheme_member_makecurrent,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,unpublished_notcurrent).
possibleaction1(scheme_member_makecurrent,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,published_notcurrent).
possibleaction1(scheme_member_publish,L,U,R) :- account(A,S,service_srebadges,L,U), resource(R,resource_sreschememember,RT,S,A,unpublished_current).
EOT;

// goal(s)
$goal = "possibleaction1(A,L,U,R)";

// run gprolog...
$tmpfname = tempnam(sys_get_temp_dir(), "sre");
$tmpfname1 = $tmpfname.".pl";
$handle = fopen($tmpfname1, "w");
fwrite($handle, $facts);
fwrite($handle, $rules);
fclose($handle);

$tmpfname2 = tempnam(sys_get_temp_dir(), "sre");
$handle = fopen($tmpfname2, "w");
fwrite($handle, "['".$tmpfname."'].\n");
fwrite($handle, "leash(none).\n");
fwrite($handle, "trace,findall(A,".$goal.",List).\n");
fclose($handle);

$output = array();
$rval = 0;
$cmd = "gprolog 2>&1 < ".$tmpfname2;
$ret = exec($cmd, $output, $rval);

$out = '';
foreach ($output as $o) {
	$out .= $o."\n";
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