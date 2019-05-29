
Adds feeds in JSON Feed format.


## Description 

Adds a JSON Feed to your WordPress site by adding `/feed/json` to any URL.

The JSON Feed format is a pragmatic syndication format, like RSS and Atom, but with one big difference: it's JSON instead of XML. Learn more at [jsonfeed.org](http://jsonfeed.org/).


## Installation 

1. Upload the plugin files to the `/wp-content/plugins/jsonfeed` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress


## Frequently Asked Questions 


### What is JSONFeed? 

JSON Feed, a format similar to RSS and Atom but in JSON. JSON has become the developers’ choice for APIs, and that developers will often go out of their way to avoid XML.
JSON is simpler to read and write, and it’s less prone to bugs.


### Can I add other fields to the feed? 

Yes you can! There is a filter, `json_feed_item`, that allows you to modify the items in the feed just before they're inserted into the feed itself. For example, if you want to add a link to a post author's archive page to the respective item in the feed, you can use the following code:

```
function wp_custom_json_feed_fields( $feed_item, $post ){
    $feed_item['author']['archive_link'] = get_author_posts_url( $post->post_author );

    return $feed_item;
}
add_filter( 'json_feed_item', 'wp_custom_json_feed_fields', 10, 2);
```


### Can I write information to my posts? 

This is a syndication format, which means it only represents your posts and comments as feed elements. This is read only, similar to RSS or Atom. It is not an API.


## Changelog 


### 1.3.0 
* Add comments template
* JSONFeed icon now part of repo
* Allow for multiple attachments
* Respect summary setting
* Add support for extra feeds in header


### 1.2.0 
* dshanske added as a contributor/maintainer
* Add featured image if set
* Add site icon if set
* home_page_url now actually returns the correct URL instead of always returning the homepage of the site
* Add avatar and URL to author
* Include site name in feed name in the discovery title
* Fix issue with timezone not reflecting on date


### 1.1.2 


### 1.1.1 


### 1.0 
* Initial release

