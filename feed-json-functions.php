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


function get_json_feed_data() {
	$return = array(
		'version'       => 'https://jsonfeed.org/version/1',
		'user_comment'  => sprintf( __( 'This feed allows you to read the posts from this site in any feed reader that supports the JSON Feed format. To add this feed to your reader, copy the following URL -- %1$s -- and add it your reader.', 'jsonfeed' ), get_json_self_link() ),
		'home_page_url' => get_link_from_json_feed( get_json_self_link() ),
		'feed_url'      => get_json_self_link(),
		'title'         => get_bloginfo( 'name' ),
		'description'   => get_bloginfo( 'description' ),
		'icon'          => get_site_icon_url(),
	);
	return array_filter( $return );
}


function get_json_comment_feed_item() {
	$comment      = get_comment();
	$comment_post = get_post( $comment->comment_post_ID );
	if ( 1 === (int) get_option( 'rss_use_excerpt' ) ) {
		$content = get_comment_excerpt();
	} else {
		$content = get_comment_text();
	}

	if ( ! is_singular() ) {
		$title = get_the_title( $comment_post->ID );
		/** This filter is documented in wp-includes/feed.php */
		$title = apply_filters( 'the_title_rss', $title );
		/* translators: Individual comment title. 1: Post title, 2: Comment author name */
		$title = sprintf( ent2ncr( __( 'Comment on %1$s by %2$s', 'jsonfeed' ) ), $title, get_comment_author_rss() );
	} else {
		$title = '';
	}

	$feed_item = array(
		'id'             => get_comment_link(),
		'url'            => get_comment_link(),
		'title'          => html_entity_decode( $title ),
		'content_html'   => $content,
		'content_text'   => wp_strip_all_tags( $content ),
		'date_published' => get_comment_date( 'Y-m-d\TH:i:sP' ),
		'author'         => get_json_comment_author(),
	);

	if ( ! is_singular() ) {
		$feed_item['_parent_url'] = get_permalink( $comment_post );
	}

	// If anything is an empty string or null then remove it
	$feed_item = array_filter( $feed_item );

	return apply_filters( 'json_feed_comment_item', $feed_item, get_comment() );
}

function get_json_feed_item() {
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
		'author'         => get_json_item_author(),
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

	return apply_filters( 'json_feed_item', $feed_item, get_post() );
}

function get_json_item_author() {
	return array(
		'name'   => get_the_author(),
		'url'    => get_author_posts_url( get_the_author_meta( 'ID' ) ),
		'avatar' => get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 512 ) ),
	);
}

function get_json_comment_author() {
	return array(
		'name'   => get_comment_author(),
		'url'    => get_comment_author_url(),
		'avatar' => get_avatar_url( get_comment(), array( 'size' => 512 ) ),
	);
}



function json_get_merged_tags( $post_id = null ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array();
	}
	$tags       = get_the_terms( $post, 'post_tag' );
	$categories = get_the_terms( $post, 'category' );
	$tags       = is_array( $tags ) ? $tags : array();
	$categories = is_array( $categories ) ? $categories : array();
	$tags       = array_merge( $tags, $categories );
	$tags       = wp_list_pluck( $tags, 'name', 'slug' );
	$return     = array();
	foreach ( $tags as $key => $value ) {
		if ( 'uncategorized' !== $key ) {
			$return[] = $value;
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
