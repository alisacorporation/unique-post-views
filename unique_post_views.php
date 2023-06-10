<?php
/*
Plugin Name: Unique Post Views
Description: Counts and displays unique post views using IP addresses.
Theme URI: https://github.com/alisacorporation/theme-kdb
Author: Alisa Corporation
Author URI: https://xeon.lv
Version: 1.0
License: GPL2
*/

defined( 'UPV_TABLE' ) || define( 'UPV_TABLE', 'unique_post_views' );

// Register the necessary hooks and actions
register_activation_hook( __FILE__, 'upv_create_table' );
register_deactivation_hook( __FILE__, 'upv_remove_table' );
add_action( 'template_redirect', 'upv_track_post_views' );
add_shortcode( 'post_view_count', 'upv_get_post_view_count' );

// Create the database table on plugin activation
function upv_create_table(): void {
	global $wpdb;
	$table_name      = $wpdb->prefix . UPV_TABLE;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        ip_address varchar(45) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY post_ip (post_id, ip_address)
    ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Remove the database table on plugin deactivation
function upv_remove_table(): void {
	global $wpdb;
	$table_name = $wpdb->prefix . UPV_TABLE;
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}

// Track post views and store unique IP addresses in the database
function upv_track_post_views(): void {
	if ( is_singular( 'post' ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . UPV_TABLE;
		$post_id    = get_the_ID();
		$ip_address = $_SERVER['REMOTE_ADDR'];

		$existing_view = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND ip_address = %s",
			$post_id,
			$ip_address
		) );

		if ( ! $existing_view ) {
			$wpdb->insert(
				$table_name,
				[
					'post_id'    => $post_id,
					'ip_address' => $ip_address,
				],
				[
					'%d',
					'%s',
				]
			);
		}
	}
}


if ( ! function_exists( 'upv_get_post_view_count' ) ) {
	// Retrieve the post view count
	function upv_get_post_view_count(): int {
		global $wpdb;
		$table_name = $wpdb->prefix . UPV_TABLE;
		$post_id    = get_the_ID();

		$view_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
			$post_id
		) );

		return (int) $view_count;
	}
}

