<?php
/*
Plugin Name: SRE Toolkit Web Badge
Plugin URI: 
Description: Create and administer simple website badges, e.g. for members
Version: 0.1
Author: Chris Greenhalgh
Author URI: http://www.cs.nott.ac.uk/~cmg
License: BSD-new
*/
/*
Copyright (c) 2013, The University of Nottingham
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

//===================================================================
// custom post types: scheme and member

// register to initialise
add_action( 'init', 'sretk_create_post_type' );

// on action 'init', create custom post types
function sretk_create_post_type() {
	// initial Scheme type.
	// TODO refine to include e.g. Levels of accreditation, Accreditor
	register_post_type( 'sretk_scheme',
		array(
			'label' => __( 'Scheme' ),
			'labels' => array(
				'name' => __( 'Schemes' ),
				'singular_name' => __( 'Scheme' ),
				'add_new_item' => __( 'Add New Scheme' ),
				'edit_item' => __( 'Edit Scheme' ),
				'new_item' => __( 'New Scheme' ),
				'view_item' => __( 'View Scheme' ),
				'search_item' => __( 'Search Schemes' ),
				'not_found' => __( 'No scheme found' ),
				'not_found_in_trash' => __( 'No scheme found in Trash' )
			),
		'description' => __( 'A badge or label that shows you recognise specific websites' ),
		'public' => true,
		//'exclude_from_search' - inherited from public
		//'publicly_queryable' - inherited from public
		//'show_ui' - inherited from public
		//'show_in_nav_menus' - inherited from public
		//'show_in_menu' - inherited from show_ui
		//'show_in_admin_bar' - inherited fro show_in_menu
		'menu_position' => 40, // at the top, 5=Posts
		//'menu_icon'
		//'capability_type' - default 'post'
		//'capabilities' - see http://codex.wordpress.org/Function_Reference/register_post_type
		//'map_meta_cap'
		'hierarchical' => false, // default
		'supports' => array(
			'title',
			'editor', // i.e. content
			'thumbnail', // i.e. featured image
			// 'excerpt', 'trackbacks', 'custom-fields', 
			// 'comments', 'revisions', 'page-attributes',
			// 'post-formats'
			),
		//'register_meta_box_cb' - fn to call add_/remove_meta_box
		//'taxonomies' - or register_taxonomy_for_object_type
		'has_archive' => true,
		//'rewrite' - default true
		//'query_var' - default true ($post_type)
		//'can_export' - default true
		)
	);
	register_post_type( 'sretk_scheme_member',
		array(
			'label' => __( 'Member' ),
			'labels' => array(
				'name' => __( 'Members' ),
				'singular_name' => __( 'Member' ),
				'add_new_item' => __( 'Add New Member' ),
				'edit_item' => __( 'Edit Member' ),
				'new_item' => __( 'New Member' ),
				'view_item' => __( 'View Member' ),
				'search_item' => __( 'Search Members' ),
				'not_found' => __( 'No member found' ),
				'not_found_in_trash' => __( 'No member found in Trash' )
			),
		'description' => __( 'A member of (or applicant to) a Scheme' ),
		'public' => true,
		//'exclude_from_search' - inherited from public
		//'publicly_queryable' - inherited from public
		//'show_ui' - inherited from public
		//'show_in_nav_menus' - inherited from public
		//'show_in_menu' - inherited from show_ui
		//'show_in_admin_bar' - inherited fro show_in_menu
		'menu_position' => 42, // at the top, 5=Posts
		//'menu_icon'
		//'capability_type' - default 'post'
		//'capabilities' - see http://codex.wordpress.org/Function_Reference/register_post_type
		//'map_meta_cap'
		'hierarchical' => false, // default
		'supports' => array(
			'title',
			'editor', // i.e. content
			// 'thumbnail', i.e. featured image
			// 'excerpt', 'trackbacks', 
			'custom-fields', 
			// 'comments', 'revisions', 'page-attributes',
			// 'post-formats'
			// Didn't work: 'sretk_scheme_mstate',
			),
		//'register_meta_box_cb' - fn to call add_/remove_meta_box
		'taxonomies' => array( 'sretk_scheme_mstate' ), // - or register_taxonomy_for_object_type
		'has_archive' => true,
		//'rewrite' - default true
		//'query_var' - default true ($post_type)
		//'can_export' - default true
		)
	);
	// custom taxonomy of scheme member status
	$args = array(
			'labels'                     => 
				array(
				'name'                       => _x( 'States', 'Taxonomy General Name', 'text_domain' ),
				'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'text_domain' ),
				'menu_name'                  => __( 'States', 'text_domain' ),
				'all_items'                  => __( 'All States', 'text_domain' ),
				'parent_item'                => __( 'Parent State', 'text_domain' ),
				'parent_item_colon'          => __( 'Parent State:', 'text_domain' ),
				'new_item_name'              => __( 'New State Name', 'text_domain' ),
				'add_new_item'               => __( 'Add New State', 'text_domain' ),
				//'edit_item'                  => __( 'Edit State', 'text_domain' ),
				//'update_item'                => __( 'Update State', 'text_domain' ),
				'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
				//'search_items'               => __( 'Search states', 'text_domain' ),
				'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
				'choose_from_most_used'      => __( 'Choose from the most used states', 'text_domain' ),
				'not_found'					 => __( 'No states found' ),
				),
			'hierarchical'               => true,
			'public'                     => false,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'capabilities' => array(
					// see http://wordpress.stackexchange.com/questions/16588/how-to-assign-multiple-roles-for-capabilities-array-withini-register-taxonomy-fu
					// TODO stop this
		            'manage_terms' => 'manage_options', //by default only admin
		            'edit_terms' => 'manage_options',
		            'delete_terms' => 'manage_options',
		            'assign_terms' => 'edit_posts'  // means administrator', 'editor', 'author', 'contributor'
					), 
	);
	
	register_taxonomy( 'sretk_scheme_mstate', 'sretk_scheme_member', $args );
	// built-in states
	if (!term_exists( 'New', 'sretk_scheme_mstate' )) {
		wp_insert_term(
				'New', // the term
				'sretk_scheme_mstate', // the taxonomy
				array(
						'description'=> 'A new application',
						'slug' => 'new',
						//'parent'=> $parent_term_id
				)
		);
	}
	if (!term_exists( 'Current', 'sretk_scheme_mstate' )) {
		wp_insert_term(
				'Current', // the term
				'sretk_scheme_mstate', // the taxonomy
				array(
						'description'=> 'A current member',
						'slug' => 'current',
						//'parent'=> $parent_term_id
				)
		);
	}
}

//===================================================================
// custom meta data editor(s) for member post type
// based on http://codex.wordpress.org/Function_Reference/add_meta_box example

// http://stackoverflow.com/questions/3760222/how-to-include-css-and-jquery-in-my-wordpress-plugin
function sretk_css_and_js() {
	wp_register_style('sretk_css_and_js', plugins_url('style.css',__FILE__ ));
	wp_enqueue_style('sretk_css_and_js');
	//wp_register_script( 'sretk_css_and_js', plugins_url('your_script.js',__FILE__ ));
	//wp_enqueue_script('sretk_css_and_js');
}
add_action( 'admin_init','sretk_css_and_js');

/* Define the custom box */

add_action( 'add_meta_boxes', 'sretk_add_custom_box' );

// backwards compatible (before WP 3.0)
// add_action( 'admin_init', 'sretk_add_custom_box', 1 );

/* Do something with the data entered */
add_action( 'save_post', 'sretk_save_postdata' );

/* Adds a box to the main column on the Post and Page edit screens */
function sretk_add_custom_box() {
	$screens = array( 'sretk_scheme_member', 'post' );
	foreach ($screens as $screen) {
		add_meta_box(
				'sretk_sectionid',
				__( 'Member properties' ),
				'sretk_inner_custom_box',
				$screen
		);
	}
}

// does this go at the top level? not sure...
require_wp_db();

/* Prints the box content */
function sretk_inner_custom_box( $post ) {
	global $wpdb;
	
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'sretk_noncename' );

	// The actual fields for data entry
	// Use get_post_meta to retrieve an existing value from the database and use the value for the form
	$url_prefix_value = get_post_meta( $post->ID, '_sretk_url_prefix', true );
	echo '<p><label for="sretk_url_prefix">';
	_e( "Member website URL" );
	echo '</label><br/> ';
	echo '<input type="text" id="sretk_url_prefix" name="sretk_url_prefix" value="'.esc_attr($url_prefix_value).'" class="code" />';
	echo '</p>';
	
	$scheme_id_value = get_post_meta( $post->ID, '_sretk_scheme_id', true );
	echo '<p><label for="sretk_scheme_id">';
	_e( "Scheme" );
	echo '</label><br/> ';
	// drop-down chooser
	// TODO restrict to schemes created by the current user as post_author
	$schemes = $wpdb->get_results( 
		"
		SELECT ID, post_title 
		FROM $wpdb->posts
		WHERE post_type = 'sretk_scheme' 
		ORDER BY post_title ASC
		", ARRAY_A
	);
	// 			AND post_author = 5
	
	echo '<select id="sretk_scheme_id" required name="sretk_scheme_id" >';
	echo '<option value="">None</option>';
	foreach ( $schemes as $scheme ) {
		$selected = '';
		if ($scheme['ID']==$scheme_id_value)
			$selected = "selected ";
		echo '<option value="'.esc_attr($scheme['ID']).'" '.$selected.'>'.esc_attr($scheme['post_title']).'</option>';
	}
	echo '</select>';
	echo '</p>';
	
}

/* When the post is saved, saves our custom data */
function sretk_save_postdata( $post_id ) {

	// First we need to check if the current user is authorised to do this action.
	//if ( 'page' == $_POST['post_type'] ) {
	//	if ( ! current_user_can( 'edit_page', $post_id ) )
	//		return;
	//} else {
	if ( ! current_user_can( 'edit_post', $post_id ) )
		return;
	//}

	// Secondly we need to check if the user intended to change this value.
	if ( ! isset( $_POST['sretk_noncename'] ) || ! wp_verify_nonce( $_POST['sretk_noncename'], plugin_basename( __FILE__ ) ) )
		return;

	// Thirdly we can save the value to the database

	//if saving in a custom table, get post_ID
	$post_ID = $_POST['post_ID'];
	//sanitize user input
	$url_prefix_value = sanitize_text_field( $_POST['sretk_url_prefix'] );
	$scheme_id_value = sanitize_text_field( $_POST['sretk_scheme_id'] );
	
	// Do something with $mydata
	// either using
	//add_post_meta($post_ID, '_my_meta_value_key', $mydata, true) or
	update_post_meta($post_ID, '_sretk_url_prefix', $url_prefix_value);
	update_post_meta($post_ID, '_sretk_scheme_id', $scheme_id_value);
	// or a custom table (see Further Reading section below)
}

//===================================================================
// Register custom template/view(s)

// see http://wp.tutsplus.com/tutorials/plugins/a-guide-to-wordpress-custom-post-types-creation-display-and-meta-boxes/
add_filter( 'template_include', 'sretk_include_template_function', 1 );

function sretk_include_template_function( $template_path ) {
	if ( get_post_type() == 'sretk_scheme' ) {
		if ( is_single() ) {
			// checks if the file exists in the theme first,
			// otherwise serve the file from the plugin
			if ( $theme_file = locate_template( array ( 'single-sretk_scheme.php' ) ) ) {
				$template_path = $theme_file;
			} else {
				$template_path = plugin_dir_path( __FILE__ ) . '/single-sretk_scheme.php';
			}
		}
	}
	return $template_path;
}
