<?php
/*
Plugin Name: Synthesis Accelerator Cache Purge
Plugin URI: http://websynthesis.com/
Description: Synthesis Accelerator cache purge utility.  No configuration required.
Version: 0.4
Author: Ron Rennick
Author URI: http://copyblogger.com/
Network: true
*/

/*
based on Nginx Proxy Cache Purge 0.3 by Ron Rennick (http://ronandandrea.com/)
based on Nginx Proxy Cache Purge 0.9.4 by John Levandowski (http://johnlevandowski.com/)

user agent list from WPTouch
(iphone|ipod|incognito|webmate|android|dream|cupcake|froyo|blackberry|mobile|webos|s8000|bada)

*/

// Add support for various X_forward v

add_filter( 'pre_comment_user_ip', 'nginx_cache_real_user_ip');

function nginx_cache_real_user_ip()
{    
	$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
	if (!empty($_SERVER['X_FORWARDED_FOR'])) {
		$X_FORWARDED_FOR = explode(',', $_SERVER['X_FORWARDED_FOR']);
		if (!empty($X_FORWARDED_FOR)) {
			$REMOTE_ADDR = trim($X_FORWARDED_FOR[0]);
		}
	}

	return preg_replace('/[^0-9a-f:\., ]/si', '', $REMOTE_ADDR);
}

class RA_Nginx_Proxy_Cache_Purge {

	/*
	slugs used by the nginx cache purge module to purge the cache
	*/
	var $_purge_slug;
	var $_mobile_purge_slug;
	/*
	flag for purging comment feeds
	*/
	var $_purge_comment_feeds = true;
	/*
	Start me up
	*/
	function __construct() {

		add_action( 'init', array( $this, 'init' ) );

	}
	/*
	Hook into WordPress after themes & plugins are loaded
	*/
	function init() {

		add_action( 'edit_post', array( $this, 'purge_post' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'purge_single_post' ) );
		foreach( (array) get_post_types( array( 'public' => true ) ) as $type )
			add_filter( $type . '_row_actions', array( $this, 'post_row_actions' ), 10, 2 );

		$this->_purge_slug = apply_filters( 'ranpcp_purge_slug', 'purge' );
		$mobile_slug = '';
		foreach( array( 'WPtouchPlugin', 'WPtouchPro' ) as $touch ) {

			if ( class_exists( $touch ) ) {

				$mobile_slug = 'mobilepurge';
				break;

			}
		}

		$this->_mobile_purge_slug = apply_filters( 'ranpcp_mobile_purge_slug', $mobile_slug );
		
		// turn on the stats in the footer source
		if ( apply_filters( 'ranpcp_show_stats', false ) )
			add_action( 'wp_footer', array( $this, 'wp_footer' ) );

	}
	/*
	Adhoc purge a single post
	*/
	function purge_single_post() {

		if ( ! isset( $_GET['post_id'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ranpcp-purge-post' ) )
			return;

		$post = get_post( $_GET['post_id'] );
		if ( empty( $post ) )
			return;

		$cap = apply_filters( 'ranpcp_purge_capability', 'edit_others_' . $post->post_type . 's', $post->post_type );
		if ( ! current_user_can( $cap ) )
			return;

		$this->_purge_comment_feeds = ( $post->comment_status == 'open' || $post->ping_status == 'open' );
		$this->purge_post( $post->ID, $post, false );
		wp_redirect( wp_get_referer() );
		exit;

	}
	/*
	Unhook purge on comment
	*/
	function unhook_purge() {

		remove_action( 'edit_post', array( $this, 'purge_post' ), 10, 2 );

	}
	/*
	Purge a post
	*/
	function purge_post( $post_id, $post, $all = true ) {

		$purge_urls = $this->get_purge_post_urls( $post_id, $post->post_type, $post->post_status, $all );
		$purge_urls = apply_filters( 'ranpcp_post_purge_urls', $purge_urls, $post_id, $post->post_type, $post->post_status, $all );

		$this->purge_urls( $purge_urls );

	}
	/*
	Add action link
	*/
	function post_row_actions( $actions, $post ) {

		$cap = apply_filters( 'ranpcp_purge_capability', 'edit_others_' . $post->post_type . 's', $post->post_type );
		if( current_user_can( $cap ) )
			$actions['purge_cache'] = "<a title='" . esc_attr( __( 'Purge this item from the Synthesis Accelerator', 'nginx-cache' ) ) . "' href='" . wp_nonce_url( add_query_arg( array( 'post_id' => $post->ID ), admin_url() ), 'ranpcp-purge-post' ) . "'>" . __( 'Purge cache', 'nginx-cache' ) . '</a>';

		return $actions;

	}
	/*
	Build a list of post urls to purge
	*/
	function get_purge_post_urls( $post_id, $post_type, $post_status, $all ) {

		$urls = array();

		if ( in_array( $post_type, array( 'nav_menu_item', 'revision' ) ) || in_array( $post_status, array( 'future', 'inherit', 'auto-draft' ) ) )
			return $urls;

		// add this post
		if ( $post_status == 'publish' ) {

			$urls[$post_type] = $permalink = get_permalink( $post_id );
			if ( $this->_purge_comment_feeds )
				$urls[$post_type . '_feed'] = $urls[$post_type] . 'feed/';

		}

		if ( ! $all )
			return $urls;

		// add home page
		$urls['home'] = home_url( '/' );

		// add site feeds
		if ( $post_type != 'page' ) {

			$urls['home_feed'] = $urls['home'] . 'feed/';
			if ( $this->_purge_comment_feeds )
				$urls['home_comment_feed'] = $urls['home'] . 'comments/feed/';

		}

		// taxonomy archives
		$taxonomies = get_object_taxonomies( $post_type );
		if ( ! empty( $taxonomies ) ) {

			$tax_urls = $this->get_purge_taxonomy_urls( $post_id, $taxonomies );
			$urls = array_merge( $urls, $tax_urls );

		}

		if ( $post_type != 'post' || get_option( 'show_on_front' ) != 'page' )
			return $urls;

		// blog page
		$blog_page = get_option( 'page_for_posts' );
		$urls['blog'] = get_permalink( $blog_page );
		
		return $urls;

	}
	/*
	Build a list of taxonomy urls to purge
	*/
	function get_purge_taxonomy_urls( $post_id, $taxonomies ) {

		global $wp_rewrite;
		$urls = array();
		
		foreach( (array)$taxonomies as $tax ) {

			$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'slugs' ) );
			if ( empty( $terms ) )
				continue;

			$tax_base = $wp_rewrite->get_extra_permastruct( $tax );
			foreach( $terms as $slug ) {

				$tax_slug = $tax . '_' . $slug;
				$urls[$tax_slug] = home_url( trailingslashit( str_replace( "%{$tax}%", $slug, $tax_base ) ) );
				$urls[$tax_slug . '_feed'] = $urls[$tax_slug] . 'feed/';

			}
		}

		return $urls;

	}
	/*
	purge an array of urls passed as a parameter
	*/
	function purge_urls( $args = array() ) {

		if ( ! $this->_purge_slug )
			return;

		$urls = array();

		foreach( (array)$args as $url ) {

			$url = trim( $url );
			if ( ! $url )
				continue;

			if ( ! preg_match( '|^(.*://[^/]+)(/.*)$|', $url, $m ) )
				continue;

			$urls[] = $m[1] . '/' . $this->_purge_slug . $m[2];
			if ( $this->_mobile_purge_slug )
				$urls[] = $m[1] . '/' . $this->_mobile_purge_slug . $m[2];

		}

		if ( empty( $urls ) )
			return;

		foreach( array_unique( $urls ) as $uri )
			wp_remote_get( $uri );

	}
	/*
	show stats in footer source
	*/
	function wp_footer() {

		$content = apply_filters( 'ranpcp_stats_format', '<!-- Page created in %.2f seconds from %d queries on %s -->' );
		$dt_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' ); 
		echo sprintf( $content, timer_stop( 0, 2 ), get_num_queries(), date( $dt_format ) );

	}
}

global $ra_nginx_proxy_cache_purge;
$ra_nginx_proxy_cache_purge = new RA_Nginx_Proxy_Cache_Purge();

function ranpcp_purge_urls( $args ) {

	global $ra_nginx_proxy_cache_purge;

	if ( is_object( $ra_nginx_proxy_cache_purge ) )
		$ra_nginx_proxy_cache_purge->purge_urls( $args );

}

?>
