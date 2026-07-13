<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class ServerScanner
{
    public function scan(): array
    {
        global $wpdb;

        return array(
            'php_version'      => PHP_VERSION,
            'database_version' => $wpdb->db_version(),
            'web_server'       => sanitize_text_field($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
            'memory_limit'     => ini_get('memory_limit') ?: 'Unknown',
            'wp_memory_limit'  => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : 'Unknown',
            'upload_limit'     => size_format(wp_max_upload_size()),
            'max_execution'    => (int) ini_get('max_execution_time'),
            'https'            => is_ssl(),
            'opcache'          => function_exists('opcache_get_status') && (bool) ini_get('opcache.enable'),
            'object_cache'     => wp_using_ext_object_cache(),
        );
    }
}
