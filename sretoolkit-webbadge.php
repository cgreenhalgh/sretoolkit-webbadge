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

add_action( 'init', 'create_post_type' );
function create_post_type() {
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
						'slug' => 'new',
						//'parent'=> $parent_term_id
				)
		);
	}
}

