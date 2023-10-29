<?php
/*
Plugin Name: JSON Feed
Plugin URI: https://github.com/manton/jsonfeed-wp/
Description: Adds a feed of recent posts in JSON Feed format.
Version: 1.4.5
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
	header( 'Access-Control-Allow-Origin: *' );

	if ( $for_comments ) {
		load_template( __DIR__ . '/feed-json-comments.php' );
	} else {

		load_template( __DIR__ . '/feed-json.php' );
	}
}

add_filter( 'feed_content_type', 'json_feed_content_type', 10, 2 );
function json_feed_content_type( $content_type, $type ) {
	if ( 'json' === $type ) {
		return 'application/feed+json';
	}
	return $content_type;
}


function json_feed_w3tc_is_cacheable_content_type( $types ) {
	$types[] = 'application/feed+json';
	return array_unique( $types );
}

add_filter( 'w3tc_is_cacheable_content_type', 'json_feed_w3tc_is_cacheable_content_type' );

add_action( 'wp_head', 'json_feed_link' );
function json_feed_link() {
	printf(
		'<link rel="alternate" type="application/feed+json" title="%s &raquo; JSON Feed" href="%s" />' . PHP_EOL,
		esc_attr( get_bloginfo( 'name' ) ),
		esc_url( get_feed_link( 'json' ) )
	);
}

/**
 * Display the links to the extra feeds such as category feeds.
 *
 *
 * @param array $args Optional arguments.
 */
function json_feed_links_extra( $args = array() ) {
	$defaults = array(
		/* translators: Separator between blog name and feed type in feed links */
		'separator'     => _x( '&raquo;', 'feed link', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: post title */
		'singletitle'   => __( '%1$s %2$s %3$s Comments Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: category name */
		'cattitle'      => __( '%1$s %2$s %3$s Category Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: tag name */
		'tagtitle'      => __( '%1$s %2$s %3$s Tag Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: term name, 4: taxonomy singular name */
		'taxtitle'      => __( '%1$s %2$s %3$s %4$s Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: author name  */
		'authortitle'   => __( '%1$s %2$s Posts by %3$s Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: search phrase */
		'searchtitle'   => __( '%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed', 'jsonfeed' ),
		/* translators: 1: blog name, 2: separator(raquo), 3: post type name */
		'posttypetitle' => __( '%1$s %2$s %3$s Feed', 'jsonfeed' ),
	);
	$args     = wp_parse_args( $args, $defaults );
	if ( is_singular() ) {
		$id       = 0;
		$post     = get_post( $id );
		$comments = apply_filters( 'jsonfeed_comments_feed_enable', true );
		if ( $comments && ( comments_open() || pings_open() || $post->comment_count > 0 ) ) {
			$title = sprintf( $args['singletitle'], get_bloginfo( 'name' ), $args['separator'], the_title_attribute( array( 'echo' => false ) ) );
			$href  = get_post_comments_feed_link( $post->ID, 'json' );
		}
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$post_type_obj = get_post_type_object( $post_type );
		$title         = sprintf( $args['posttypetitle'], get_bloginfo( 'name' ), $args['separator'], $post_type_obj->labels->name );
		$href          = get_post_type_archive_feed_link( $post_type_obj->name, 'json' );
	} elseif ( is_category() ) {
		$term = get_queried_object();
		if ( $term ) {
			$title = sprintf( $args['cattitle'], get_bloginfo( 'name' ), $args['separator'], $term->name );
			$href  = get_category_feed_link( $term->term_id, 'json' );
		}
	} elseif ( is_tag() ) {
		$term = get_queried_object();
		if ( $term ) {
			$title = sprintf( $args['tagtitle'], get_bloginfo( 'name' ), $args['separator'], $term->name );
			$href  = get_tag_feed_link( $term->term_id, 'json' );
		}
	} elseif ( is_tax() ) {
		$term  = get_queried_object();
		$tax   = get_taxonomy( $term->taxonomy );
		$title = sprintf( $args['taxtitle'], get_bloginfo( 'name' ), $args['separator'], $term->name, $tax->labels->singular_name );
		$href  = get_term_feed_link( $term->term_id, $term->taxonomy, 'json' );
	} elseif ( is_author() ) {
		$author_id = intval( get_query_var( 'author' ) );
		$title     = sprintf( $args['authortitle'], get_bloginfo( 'name' ), $args['separator'], get_the_author_meta( 'display_name', $author_id ) );
		$href      = get_author_feed_link( $author_id, 'json' );
	} elseif ( is_search() ) {
		$title = sprintf( $args['searchtitle'], get_bloginfo( 'name' ), $args['separator'], get_search_query( false ) );
		$href  = get_search_feed_link( '', 'json' );
	} elseif ( is_post_type_archive() ) {
		$title         = sprintf( $args['posttypetitle'], get_bloginfo( 'name' ), $args['separator'], post_type_archive_title( '', false ) );
		$post_type_obj = get_queried_object();
		if ( $post_type_obj ) {
			$href = get_post_type_archive_feed_link( $post_type_obj->name, 'json' );
		}
	}
	if ( isset( $title ) && isset( $href ) ) {
		printf( '<link rel="alternate" type="%s" title="%s" href="%s" />', esc_attr( feed_content_type( 'json' ) ), esc_attr( $title ), esc_url( $href ) );
		echo PHP_EOL;
	}
}
add_filter( 'wp_head', 'json_feed_links_extra' );

/**
 * Add `json` as "supported feed type" for the WebSub implementation.
 *
 *
 * @param array $feed_types The list of supported feed types.
 *
 * @return array $feed_types The filtered list of supported feed types.
 */
function json_feed_websub( $feed_types ) {
	$feed_types[] = 'json';
	return $feed_types;
}
add_filter( 'pubsubhubbub_supported_feed_types', 'json_feed_websub' );

require_once __DIR__ . '/feed-json-functions.php';
