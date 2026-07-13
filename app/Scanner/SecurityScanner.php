<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class SecurityScanner
{
    public function scan(): array
    {
        return array(
            'https'            => is_ssl(),
            'debug_disabled'   => ! (defined('WP_DEBUG') && WP_DEBUG),
            'file_editor_off'  => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'xmlrpc_enabled'   => (bool) apply_filters('xmlrpc_enabled', true),
            'default_admin'    => username_exists('admin') !== false,
        );
    }
}
