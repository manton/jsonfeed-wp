<?php

$feed_items = array();

while ( have_posts() ) {
	the_post();
	if ( 1 === (int) get_option( 'rss_use_excerpt' ) ) {
		$content = get_the_json_excerpt_feed();
	} else {
		$content = get_the_content_feed( 'json' );
	}

	$feed_item = array(
		'id'             => get_permalink(),
		'url'            => get_permalink(),
		'title'          => html_entity_decode( get_the_title() ),
		'content_html'   => $content,
		'content_text'   => wp_strip_all_tags( $content ),
		'date_published' => get_the_date( 'Y-m-d\TH:i:sP' ),
		'date_modified'  => get_the_modified_date( 'Y-m-d\TH:i:sP' ),
		'author'         => array(
			'name'   => get_the_author(),
			'url'    => get_author_posts_url( get_the_author_meta( 'ID' ) ),
			'avatar' => get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 512 ) ),
		),
		'image'          => get_the_post_thumbnail_url( null, 'full' ), // If there is a set featured image
		'tags'           => json_get_merged_tags(), // Tags is a merge of the category and the tags names
	);

	// Only add custom excerpts not generated ones
	if ( has_excerpt() ) {
		$feed_item['summary'] = get_the_excerpt();
	}
	// If anything is an empty string or null then remove it
	$feed_item = array_filter( $feed_item );

	$attachment = get_attachment_json_info();
	if ( ! empty( $attachment ) ) {
		$feed_item['attachments'] = array(
			$attachment,
		);
	}

	$feed_items[] = apply_filters( 'json_feed_item', $feed_item, get_post() );
}

$feed_json = array(
	'version'       => 'https://jsonfeed.org/version/1',
	'user_comment'  => 'This feed allows you to read the posts from this site in any feed reader that supports the JSON Feed format. To add this feed to your reader, copy the following URL -- ' . get_json_self_link() . ' -- and add it your reader.',
	'home_page_url' => get_link_from_json_feed( get_json_self_link() ),
	'feed_url'      => get_json_self_link(),
	'title'         => get_bloginfo( 'name' ),
	'description'   => get_bloginfo( 'description' ),
	'items'         => $feed_items,
);

$icon = get_site_icon_url();

// Only add icon if icon is set
if ( $icon ) {
	$feed_json['icon'] = $icon;
}

$feed_json = apply_filters( 'json_feed_feed', $feed_json );

// The JSON_PRETTY_PRINT and JSON_UNESCAPED slashes make the minimum version requirement on this PHP5.4
echo wp_json_encode( $feed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
