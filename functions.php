<?php 
/* general helper functions for sretoolkit-webbadge
 * 
 */
 function scheme_mstate_is_current($member_id) {
 	$terms = get_the_terms( $member_id, 'sretk_scheme_mstate' );
 	if ( $terms && ! is_wp_error( $terms ) ) {
 		foreach ($terms as $term) {
 			if ($term->slug == 'current')
 				return true;
 		}
 	}
	return false;
 }
 