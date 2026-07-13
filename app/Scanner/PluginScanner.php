<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class PluginScanner
{
    public function scan(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active = (array) get_option('active_plugins', array());
        $updates = get_site_transient('update_plugins');
        $update_count = is_object($updates) && isset($updates->response) ? count($updates->response) : 0;

        return array(
            'installed'     => count($plugins),
            'active'        => count($active),
            'inactive'      => max(0, count($plugins) - count($active)),
            'updates'       => $update_count,
            'must_use'      => function_exists('get_mu_plugins') ? count(get_mu_plugins()) : 0,
        );
    }
}
