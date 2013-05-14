<?php 
/*
 * Get image for scheme, optionally checking member and referrer.
 * 
 * Query parameters:
 * - scheme_id N
 * - member_id N (optional)
 */
require_once( dirname(__FILE__) . '/../../../wp-load.php' );

// parameters
$scheme_id = isset($_GET['scheme_id']) ? $_GET['scheme_id'] : '';
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : '';

if (!$scheme_id) {
	status_header( 400 );
	die( '400 &#8212; Bad Request (scheme_id undefined).' );
}

$post = get_post( $scheme_id );

if (!$post) {
	status_header( 404 );
	die( '404 &#8212; File Not Found (post '+$scheme_id+').' );
}

if ($post->post_type!='sretk_scheme') {
	status_header( 400 );
	die( '400 &#8212; Bad Request (post '+$scheme_id+' is not a scheme: '+$post->post_type+').' );
}

// TODO check member status...

$image_id = get_post_thumbnail_id($scheme_id);
if (!$image_id) {
	status_header( 404 );
	die( '404 &#8212; File Not Found (scheme '+$scheme_id+' has no featured image).' );
}

// should be full pathname
$file = get_attached_file( $image_id );
if (!$file) {
	status_header( 404 );
	die( '404 &#8212; File Not Found (scheme '+$scheme_id+' image '+$image_id+').' );
}

if ( !is_file( $file ) ) {
	status_header( 404 );
	die( '404 &#8212; File not found (on filesystem).' );
}

//=============================================================================================
// Rest is based on wp-includes/ms-files.php
// serve image file...
$mime = wp_check_filetype( $file );
if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
	$mime[ 'type' ] = mime_content_type( $file );

if( $mime[ 'type' ] )
	$mimetype = $mime[ 'type' ];
else
	$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );

header( 'Content-Type: ' . $mimetype ); // always send this
if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
	header( 'Content-Length: ' . filesize( $file ) );

// Optional support for X-Sendfile and X-Accel-Redirect
// Doesn't seem to work for me
/*if ( WPMU_ACCEL_REDIRECT ) {
	header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '', $file ) );
	exit;
} elseif ( WPMU_SENDFILE ) {
	header( 'X-Sendfile: ' . $file );
	exit;
}
*/

$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
$etag = '"' . md5( $last_modified ) . '"';
header( "Last-Modified: $last_modified GMT" );
header( 'ETag: ' . $etag );
header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

// Support for Conditional GET
$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
	$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
// If string is empty, return 0. If not, attempt to parse into a timestamp
$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

// Make a timestamp for our most recent modification...
$modified_timestamp = strtotime($last_modified);

if ( ( $client_last_modified && $client_etag )
		? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
		: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
) {
	status_header( 304 );
	exit;
}

// If we made it this far, just serve the file
readfile( $file );
