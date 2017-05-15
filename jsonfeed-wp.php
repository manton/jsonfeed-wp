<?php
defined( 'ABSPATH' ) or die( "WordPress plugin can't be loaded directly." );

/**************************************************************************

Plugin Name:  JSON Feed (jsonfeed.org)
Version:      1.0
Description:  Adds a feed of recent posts in JSON Feed format.
Author:       Manton Reece and Daniel Jalkut

**************************************************************************/

add_action('init', 'setup_feed_rewrite');
add_filter('feed_content_type', 'json_feed_content_type', 10, 2);

function setup_feed_rewrite()
{
    // Register our function as the feed generator for the /feed/json URL
    add_feed('json', 'generateJSONFeed');

    // Have to do this to get the new rewrite rule into WP's DB
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function json_feed_content_type( $content_type, $type ) {
	if ('json' === $type) {
		return 'application/json';
	}
	return $content_type;
}

function generateJSONFeed()
{
   load_template( dirname( __FILE__ ) . '/feed-template.php' );
}
?>