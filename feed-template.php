<?php


// Reproduction of self_link with a return and without URL escaping
function get_json_self_link() {
	$host = wp_parse_url( home_url() );
	return apply_filters( 'self_link', set_url_scheme( 'http://' . $host['host'] . wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
}

function get_attachment_json_info() {
	if ( post_password_required() ) {
		return null;
	}

	foreach ( (array) get_post_custom() as $key => $val ) {
		if ( 'enclosure' === $key ) {
			foreach ( (array) $val as $enc ) {
				$enclosure = explode( "\n", $enc );

				// only get the first element, e.g. audio/mpeg from 'audio/mpeg mpga mp2 mp3'
				$t    = preg_split( '/[ \t]/', trim( $enclosure[2] ) );
				$type = $t[0];

				return array(
					'url'           => trim( $enclosure[0] ),
					'mime_type'     => $type,
					'size_in_bytes' => (int) $enclosure[1],
				);
			}
		}
	}
}

function get_link_from_json_feed( $link ) {
	global $wp_rewrite;
	$arg = $wp_rewrite->get_feed_permastruct();
	// If empty this site does not have pretty permalinks enabled
	if ( empty( $arg ) ) {
		wp_parse_str( wp_parse_url( $link, PHP_URL_QUERY ), $query_args );
		unset( $query_args['feed'] );
		return add_query_arg( $query_args, home_url( '/' ) );
	} else {
		$arg  = str_replace( '%feed%', 'json', $arg );
		$arg  = preg_replace( '#/+#', '/', "/$arg" );
		$link = str_replace( $arg, '', $link );
	}
	return $link;
}

$feed_items = array();

while ( have_posts() ) {
	the_post();

	$feed_item = array(
		'id'             => get_permalink(),
		'url'            => get_permalink(),
		'title'          => html_entity_decode( get_the_title() ),
		'content_html'   => get_the_content_feed( 'json' ),
		'date_published' => get_the_date( 'Y-m-d\TH:i:sP' ),
		'date_modified'  => get_the_modified_date( 'Y-m-d\TH:i:sP' ),
		'author'         => array(
			'name'   => get_the_author(),
			'url'    => get_author_posts_url( get_the_author_meta( 'ID' ) ),
			'avatar' => get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 512 ) ),
		),
	);

	$attachment = get_attachment_json_info();
	if ( null !== $attachment ) {
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

$feed_json = apply_filters( 'json_feed_feed', $feed_json );

// The JSON_PRETTY_PRINT and JSON_UNESCAPED slashes make the minimum version requirement on this PHP5.4
echo wp_json_encode( $feed_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
