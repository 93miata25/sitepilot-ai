<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ServerScanner implements ScannerInterface {
    public function scan(): array {
        global $wpdb;

        return array(
            'php_version'       => PHP_VERSION,
            'database_version'  => $wpdb->db_version(),
            'server_software'   => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'Unknown', 'sitepilot-ai' ),
            'memory_limit'      => ini_get( 'memory_limit' ) ?: __( 'Unknown', 'sitepilot-ai' ),
            'wp_memory_limit'   => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Unknown', 'sitepilot-ai' ),
            'upload_limit'      => size_format( wp_max_upload_size() ),
            'max_execution_time'=> (string) ini_get( 'max_execution_time' ),
            'https'             => is_ssl(),
            'curl'              => extension_loaded( 'curl' ),
            'imagick'           => extension_loaded( 'imagick' ),
            'gd'                => extension_loaded( 'gd' ),
            'mbstring'          => extension_loaded( 'mbstring' ),
            'zip'               => extension_loaded( 'zip' ),
        );
    }
}
