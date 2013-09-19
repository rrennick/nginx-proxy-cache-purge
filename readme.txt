=== Network Nginx Proxy Cache Purge ===
Contributors: wpmuguru
Tags: network, multisite, post, custom, nginx, cache, purge
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 0.2

Event driven and on demand Nginx front end proxy cache purge utility.

== Description ==

A flexible and customizable event driven cache purge plugin to work in conjuction with Nginx configured as a front end proxy to a WordPress install. For use with either single WordPress sites or in a WordPress network. If you have a single WordPress site without custom post types or mobile support the [Nginx Proxy Cache Plugin](http://wordpress.org/extend/plugins/nginx-proxy-cache-purge/) is simpler and will probably meet your needs.

*Features*

*	purge home, posts, blog page, feeds
*	enable comment triggered purge via a filter
*	purge taxonomy archives
*	processing specific to post type and status
*	7 filters for customizing purge behavior (see guide under Installation tab)
*	on demand purge post action link
*	mobile caching support
*	auto detects WPtouch/WPtouch Pro for mobile cache purging
*	works in both networks and single sites
*	includes `ranpcp_purge_urls( $array_of_urls )` function for custom purging by other plugins

This plugin was written by [Ron Rennick](http://ronandandrea.com/) in collaboration with the [University of Mary Washington](http://umw.edu/).

[Plugin Page](http://wpebooks.com/free-plugins/network-nginx-proxy-cache-purge/)

== Installation ==

1. I Strongly recommend that you get Nginx configured as the front-end proxy prior to adding the caching. Install the [WordPress Nginx proxy cache integrator](http://wordpress.org/extend/plugins/nginx-proxy-cache-integrator/) first & follow the Nginx configuration instructions so that your install is working properly with Nginx and the plugin.
1. You can install the Network Nginx Proxy Cache Purge plugin in either the `/wp-content/mu-plugins` directory or 
1. Upload the entire `network-nginx-proxy-cache-purge` folder to the `/wp-content/plugins/` directory
1. Network activate the plugin in a WordPress network 
1. In single WP, activate the plugin through the 'Plugins' screen 
1. Build Nginx with the [cache purge module](http://labs.frickle.com/nginx_ngx_cache_purge/)
1. Configure Nginx per [Network Nginx Proxy Cache Purge Configuration Guide](http://wpebooks.com/wp-content/plugins/download-monitor/download.php?id=3)

== Changelog ==

= 0.3 =
* fix parameter on get_object_terms
* automatically include custom taxonomies on CPTs
* add purge action link to public CPTs

= 0.2 =
* Original version.

