<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'sitepilot_ai_scheduled_scan' );

delete_option( 'sitepilot_ai_installed_at' );
delete_option( 'sitepilot_ai_version' );
delete_option( 'sitepilot_ai_last_scan' );
delete_option( 'sitepilot_ai_scan_frequency' );
delete_option( 'sitepilot_ai_disable_xmlrpc' );
delete_option( 'sitepilot_ai_security_headers' );
delete_transient( 'sitepilot_ai_scan_results' );

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sitepilot_ai_scans' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sitepilot_ai_activity' );
