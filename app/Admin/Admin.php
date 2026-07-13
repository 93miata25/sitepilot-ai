<?php
namespace SitePilotAI\Admin;

use SitePilotAI\Scanner\ScannerManager;

if (! defined('ABSPATH')) {
    exit;
}

final class Admin
{
    public function register(): void
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('wp_ajax_sitepilot_ai_scan', array($this, 'ajax_scan'));
    }

    public function menu(): void
    {
        add_menu_page(
            __('SitePilot AI', 'sitepilot-ai'),
            __('SitePilot AI', 'sitepilot-ai'),
            'manage_options',
            'sitepilot-ai',
            array($this, 'dashboard'),
            'dashicons-performance',
            58
        );
    }

    public function assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_sitepilot-ai') {
            return;
        }

        wp_enqueue_style('sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/css/admin.css', array(), SITEPILOT_AI_VERSION);
        wp_enqueue_script('sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/js/admin.js', array(), SITEPILOT_AI_VERSION, true);
        wp_localize_script('sitepilot-ai-admin', 'SitePilotAI', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sitepilot_ai_scan'),
            'scanning' => __('Scanning…', 'sitepilot-ai'),
            'error'    => __('The scan could not be completed.', 'sitepilot-ai'),
        ));
    }

    public function dashboard(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'sitepilot-ai'));
        }

        $scan = (new ScannerManager())->run(false);
        require SITEPILOT_AI_PATH . 'templates/dashboard.php';
    }

    public function ajax_scan(): void
    {
        check_ajax_referer('sitepilot_ai_scan', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'sitepilot-ai')), 403);
        }

        wp_send_json_success((new ScannerManager())->run(true));
    }
}
