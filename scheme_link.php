<?php 
/*
 * Incoming link from member scheme image/button - gives tailored view of scheme, optionally checking member and referrer.
 * 
 * Query parameters:
 * - scheme_id N
 * - member_id N (optional)
 */
define('WP_USE_THEMES', true);
require_once( dirname(__FILE__) . '/../../../wp-load.php' );

// parameters
$scheme_id = isset($_GET['scheme_id']) ? $_GET['scheme_id'] : '';
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : '';
$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

if (!$scheme_id) {
	status_header( 400 );
	die( '400 &#8212; Bad Request (scheme_id undefined).' );
}

// from WP->main
GLOBAL $wp;
$wp->init();

GLOBAL $member, $member_id;

GLOBAL $referer_invalid, $referer, $url_prefix_value;
$referer_invalid = false;

$wp_query = new WP_Query( array( 'post_type' => 'sretk_scheme', 'p' => $scheme_id ) );
if (have_posts()) {
	$scheme = get_post();
	
	// check member status...
	if (!$member_id) {
		status_header( 400 );
		die( '400 &#8212; Bad Request (member_id undefined).' );
	}
	
	$member = get_post( $member_id );
	
	if (!$member) {
		// found?
//		status_header( 404 );
//		die( '404 &#8212; File Not Found (post '+$member_id+').' );
		error_log( 'scheme_link.php called with unknown member_id '.$member_id );
	}
	
	if ($member && $member->post_type!='sretk_scheme_member') {
		// correct type?
//		status_header( 400 );
//		die( '400 &#8212; Bad Request (post '+$member_id+' is not a scheme member: '+$member->post_type+').' );
		error_log( 'scheme_link.php called with non-member '.$member_id.' ('.$member->post_type.')');
		$member = null;
	}

	if ($member) {
		// in scheme?
		$scheme_id_value = get_post_meta( $member->ID, '_sretk_scheme_id', true );
		if ($scheme_id_value !== $scheme_id) {
			error_log( 'scheme_link.php called with mis-matched scheme id ('.$scheme_id.' vs '.$scheme_id_value.') for member '.$member_id );
			$member = null;
		}
	}
	
		
	if ($member) {
		// current?
		$current = false;
		$terms = get_the_terms( $member->ID, 'sretk_scheme_mstate' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ($terms as $term) {
				if ($term->slug == 'current')
					$current = true;
			}
		}
		if (!$current) {
			error_log( 'scheme_link.php called with non-current member ('.$member_id.')' );
			$member = null;
		}
	}
	
	if ($member) {
		$url_prefix_value = get_post_meta( $member->ID, '_sretk_url_prefix', true );
		if ($url_prefix_value) {
			if ($referer) {
				if (strncmp($referer, $url_prefix_value, strlen($url_prefix_value))) {
					error_log( 'scheme_link.php called with invalid referer ('.$referer.' vs '.$url_prefix_value.')' );
					$referer_invalid = true;					
				}
			} else {
				error_log( 'scheme_link.php called with no referer (vs '.$url_prefix_value.')' );
				$referer_invalid = true;
			}
		}		
	}
}
$wp_query->rewind_posts();

// from WP->main
//$wp->parse_request($query_args);
$wp->send_headers();
//$wp->query_posts();
$wp->handle_404();
$wp->register_globals();
do_action_ref_array('wp', array(&$this));

// from wp-blog-header
//require_once( dirname(__FILE__) . '/single-sretk_scheme.php' );
require_once( ABSPATH . WPINC . '/template-loader.php' );
