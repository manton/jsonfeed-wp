<?php
/*
Plugin Name: JSON Feed (jsonfeed.org)
Plugin URI: http://jsonfeed.org
Description: Adds a feed of recent posts in JSON Feed format.
Version: 1.1.2
Author: Manton Reece and Daniel Jalkut
Text Domain: jsonfeed
License: GPL2+
*/

defined( 'ABSPATH' ) or die( "WordPress plugin can't be loaded directly." );

// Flush the rewrite rules to enable the json feed permalink
register_activation_hook( __FILE__, 'json_feed_setup_rewrite' );
function json_feed_setup_rewrite() {
	json_feed_setup_feed();
	flush_rewrite_rules();
}

// Register the json feed rewrite rules
add_action( 'init', 'json_feed_setup_feed' );
function json_feed_setup_feed() {
	add_feed( 'json', 'json_feed_render_feed' );
}
function json_feed_render_feed() {
	load_template( dirname( __FILE__ ) . '/feed-template.php' );
}

add_filter( 'feed_content_type', 'json_feed_content_type', 10, 2 );
function json_feed_content_type( $content_type, $type ) {
	if ( 'json' === $type ) {
		return 'application/json';
	}
	return $content_type;
}

add_action( 'wp_head', 'json_feed_link' );
function json_feed_link() {
	printf(
		'<link rel="alternate" type="application/json" title="JSON Feed" href="%s" />',
		esc_url( get_feed_link( 'json' ) )
	);
}
