<?php

$feed_items = array();

while ( have_posts() ) {
	the_post();
	$feed_items[] = get_json_feed_item();
}

	$feed_json          = get_json_feed_data();
	$feed_json['items'] = $feed_items;

	$feed_json = apply_filters( 'json_feed_feed', $feed_json );
// The JSON_PRETTY_PRINT and JSON_UNESCAPED slashes make the minimum version requirement on this PHP5.4
echo wp_json_encode( $feed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
