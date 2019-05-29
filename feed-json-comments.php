<?php

$feed_items = array();
$feed_json  = get_json_feed_data();

if ( is_singular() ) {
	/* translators: Comments feed title. %s: Post title */
	$feed_json['title'] = sprintf( ent2ncr( __( 'Comments on: %s', 'jsonfeed' ) ), get_the_title_rss() );
} elseif ( is_search() ) {
	/* translators: Comments feed title. 1: Site name, 2: Search query */
	$feed_json['title'] = sprintf( ent2ncr( __( 'Comments for %1$s searching on %2$s', 'jsonfeed' ) ), get_bloginfo_rss( 'name' ), get_search_query() );
} else {
	/* translators: Comments feed title. %s: Site name */
	$feed_json['title'] = sprintf( ent2ncr( __( 'Comments for %s', 'jsonfeed' ) ), get_wp_title_rss() );
}

if ( have_comments() ) {
	while ( have_comments() ) {
		the_comment();
		$feed_items[] = apply_filters( 'json_feed_comment_item', get_json_comment_feed_item(), get_comment() );
	}
	$feed_json['items'] = $feed_items;
}

$feed_json = apply_filters( 'json_feed_feed', $feed_json );

// The JSON_PRETTY_PRINT and JSON_UNESCAPED slashes make the minimum version requirement on this PHP5.4
echo wp_json_encode( $feed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
