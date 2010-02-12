<?php
/**
 * @package WordPress
 * @subpackage Classic_Theme
 */

automatic_feed_links();

if ( function_exists('register_sidebar') ) {
    register_sidebar(array(
        'name' => 'Right sidebar',
        'id' => 'sidebar-1',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => "</div>\n",
		'before_title' => '<h3>',
		'after_title' => '</h3>',
    ));

    register_sidebar(array(
        'name' => 'Footer',
        'id' => 'footer-1',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '<h4 class="title">',
		'after_title' => '</h4>'
    ));
}

?>
