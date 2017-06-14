<?php

//via http://www.tequilafish.com/2009/02/10/php-how-to-capture-output-of-echo-into-a-local-variable/
ob_start();
self_link();
$self_link = ob_get_contents();
ob_end_clean();

function get_attachment_json_info() {
	if ( post_password_required() )
		return null;

	foreach ( (array) get_post_custom() as $key => $val ) {
		if ($key == 'enclosure') {
			foreach ( (array) $val as $enc ) {
				$enclosure = explode("\n", $enc);

				// only get the first element, e.g. audio/mpeg from 'audio/mpeg mpga mp2 mp3'
				$t = preg_split('/[ \t]/', trim($enclosure[2]) );
				$type = $t[0];

				return array(
					'url' => trim( $enclosure[0] ),
					'mime_type' => $type,
					'size_in_bytes' => (int)$enclosure[1]
				);
			}
		}
	}
}

$feed_items = array();

while ( have_posts() ) {
	the_post();

	$feed_item = array(
		'id' => get_permalink(),
		'url' => get_permalink(),
		'title' => get_the_title(),
		'content_html' => get_the_content_feed( 'json' ),
		'date_published' => get_gmt_from_date( get_the_date( 'Y-m-d H:i:s' ), 'c' ),
		'date_modified' => get_gmt_from_date( get_the_modified_date( 'Y-m-d H:i:s' ), 'c' ),
		'author' => array(
			'name' => get_the_author(),
		),
	);
	
	$attachment = get_attachment_json_info();
	if ( $attachment != null ) {
		$feed_item["attachments"] = array(
			$attachment
		);
	}

	$feed_items[] = apply_filters( 'json_feed_item', $feed_item, get_post() );
}

$feed_json = array(
	'version' => 'https://jsonfeed.org/version/1',
	'user_comment' => 'This feed allows you to read the posts from this site in any feed reader that supports the JSON Feed format. To add this feed to your reader, copy the following URL -- ' . $self_link . ' -- and add it your reader.',
	'home_page_url' => get_home_url(),
	'feed_url' => $self_link,
	'title' => get_bloginfo( 'name' ),
	'description' => get_bloginfo( 'description' ),
	'items' => $feed_items,
);

$feed_json = apply_filters( 'json_feed_feed', $feed_json );

echo wp_json_encode( $feed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
