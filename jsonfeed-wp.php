<?php
/*
Plugin Name: JSON Feed
Plugin URI: https://github.com/manton/jsonfeed-wp/
Description: Adds a feed of recent posts in JSON Feed format.
Version: 1.2.0
Author: Manton Reece and Daniel Jalkut
Text Domain: jsonfeed
License: GPL2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

defined( 'ABSPATH' ) || die( "WordPress plugin can't be loaded directly." );

// Flush the rewrite rules to enable the json feed permalink
register_activation_hook( __FILE__, 'json_feed_setup_rewrite' );
function json_feed_setup_rewrite() {
	json_feed_setup_feed();
	flush_rewrite_rules();
}

// Register the json feed rewrite rules
add_action( 'init', 'json_feed_setup_feed' );
function json_feed_setup_feed() {
	add_feed( 'json', 'do_feed_json' );
}
function do_feed_json( $for_comments ) {
	if ( $for_comments ) {
		load_template( dirname( __FILE__ ) . '/feed-json-comments.php' );
	} else {

		load_template( dirname( __FILE__ ) . '/feed-json.php' );
	}
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
		'<link rel="alternate" type="application/json" title="%s &raquo; JSON Feed" href="%s" />',
		esc_attr( get_bloginfo( 'name' ) ),
		esc_url( get_feed_link( 'json' ) )
	);
}

add_filter( 'pubsubhubbub_feed_urls', 'json_feed_websub' );
function json_feed_websub( $feeds ) {
	$feeds[] = get_feed_link( 'json' );
	return $feeds;
}

require_once dirname( __FILE__ ) . '/feed-json-functions.php';
