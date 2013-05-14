<?php
 /*
  * Template Name: single-sretk_scheme
  * 
  * based on http://wp.tutsplus.com/tutorials/plugins/a-guide-to-wordpress-custom-post-types-creation-display-and-meta-boxes/
  */
 
// NB our custom stripped-down header
// checks if the file exists in the theme first,
// otherwise serve the file from the plugin
if ( $theme_file = locate_template( array ( 'header-minimal.php' ) ) ) {
	$template_path = $theme_file;
} else {
	$template_path = plugin_dir_path( __FILE__ ) . '/header-minimal.php';
}
load_template( $template_path, true ); ?>
<div id="primary">
    <div id="content" role="main">
    <?php while ( have_posts() ) : the_post();?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
 
                <!-- Display featured image in right-aligned floating div -->
                <div style="float: right; margin: 10px">
                    <?php the_post_thumbnail( array( 100, 100 ) ); ?>
                </div>
 
                <!-- Display Title and Author Name -->
                <strong>Scheme: </strong><?php the_title(); ?><br />

            </header>
 
            <!-- Display movie review contents -->
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
 <?php 
 		// get the custom sretk_scheme_member post-types which are Current (custom taxonomy), published
 		// and assigned to this scheme (metadata _sretk_scheme_id)
 		$scheme_id = get_the_ID();
 		$args = array(
 					'post_type' => 'sretk_scheme_member',
 					'tax_query' => array(
 								array(
 										'taxonomy' => 'sretk_scheme_mstate',
 										'field'    => 'slug',
 										'terms'	=> 'current' // or current?
 								)
 							),
 					'post_status' => 'publish',
	 				'meta_query' => array(
	 						array(
	 								'key' => '_sretk_scheme_id',
	 								'value' => $scheme_id
	 						)
	 				)
 				);
 		$members = new WP_Query( $args );
 		if ( $members->have_posts() ) {
 			while ( $members->have_posts() ) {
 				$members->the_post(); 
?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
 
                <!-- Display Title and Author Name -->
                <strong>Member: </strong><?php the_title(); ?><br />

            </header>
 
            <!-- Display movie review contents -->
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
<?php 		}
 		}
 		wp_reset_postdata();
?>
    <?php endwhile; ?>
    </div>
</div>
<?php wp_reset_query(); ?>
<?php 
// NB our custom stripped-down footer
// checks if the file exists in the theme first,
// otherwise serve the file from the plugin
if ( $theme_file = locate_template( array ( 'footer-minimal.php' ) ) ) {
	$template_path = $theme_file;
} else {
	$template_path = plugin_dir_path( __FILE__ ) . '/footer-minimal.php';
}
load_template( $template_path, true ); ?>