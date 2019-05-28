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
	$return = array();
	foreach ( (array) get_post_custom() as $key => $val ) {
		if ( 'enclosure' === $key ) {
			foreach ( (array) $val as $enc ) {
				$enclosure = explode( "\n", $enc );

				// only get the first element, e.g. audio/mpeg from 'audio/mpeg mpga mp2 mp3'
				$t    = preg_split( '/[ \t]/', trim( $enclosure[2] ) );
				$type = $t[0];

				$return[] = array(
					'url'           => trim( $enclosure[0] ),
					'mime_type'     => $type,
					'size_in_bytes' => (int) $enclosure[1],
				);
			}
		}
	}
	return $return;
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

function json_get_merged_tags( $post_id = null ) {
	$post       = get_post( $post_id );
	$tags       = get_the_terms( $post, 'post_tag' );
	$categories = get_the_terms( $post, 'category' );
	$tags       = is_array( $tags ) ? $tags : array();
	$categories = is_array( $categories ) ? $categories : array();
	// $tags = array_merge( $tags, $categories );
	$return = array();
	foreach ( $tags as $tag ) {
		if ( 'uncategorized' !== $tag->slug ) {
			$return[] = $tag->name;
		}
	}
	return $return;
}

function get_the_json_excerpt_feed() {
	$output = get_the_excerpt();
	/**
	 * Filters the post excerpt for a feed.
	 *
	 * @since 1.2.0
	 *
	 * @param string $output The current post excerpt.
	 */
	return apply_filters( 'the_excerpt_rss', $output );
}
