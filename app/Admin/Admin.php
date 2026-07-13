<?php
namespace SitePilotAI\Admin;

use SitePilotAI\Scanner\ScannerManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Admin {
    private ScannerManager $scanner;

    public function __construct() {
        $this->scanner = new ScannerManager();
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_sitepilot_ai_run_scan', array( $this, 'ajax_run_scan' ) );
    }

    public function register_menu(): void {
        add_menu_page( __( 'SitePilot AI', 'sitepilot-ai' ), __( 'SitePilot AI', 'sitepilot-ai' ), 'manage_options', 'sitepilot-ai', array( $this, 'render_dashboard' ), 'dashicons-performance', 58 );
    }

    public function enqueue_assets( string $hook_suffix ): void {
        if ( 'toplevel_page_sitepilot-ai' !== $hook_suffix ) return;
        wp_enqueue_style( 'sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/css/admin.css', array(), SITEPILOT_AI_VERSION );
        wp_enqueue_script( 'sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/js/admin.js', array(), SITEPILOT_AI_VERSION, true );
        wp_localize_script( 'sitepilot-ai-admin', 'SitePilotAI', array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'sitepilot_ai_scan' ),
            'scanning'  => __( 'Scanning website…', 'sitepilot-ai' ),
            'completed' => __( 'Website scan completed successfully.', 'sitepilot-ai' ),
            'error'     => __( 'The scan could not be completed.', 'sitepilot-ai' ),
            'enabled'   => __( 'Enabled', 'sitepilot-ai' ),
            'disabled'  => __( 'Disabled', 'sitepilot-ai' ),
        ) );
    }

    public function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $results = $this->scanner->get_results();
        require SITEPILOT_AI_PATH . 'templates/dashboard.php';
    }

    public function ajax_run_scan(): void {
        check_ajax_referer( 'sitepilot_ai_scan', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sitepilot-ai' ) ), 403 );
        }
        wp_send_json_success( $this->scanner->run_scan( true ) );
    }
}
