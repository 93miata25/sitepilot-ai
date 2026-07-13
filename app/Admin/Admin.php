<?php
namespace SitePilotAI\Admin;

use SitePilotAI\Fixes\FixManager;
use SitePilotAI\History\HistoryRepository;
use SitePilotAI\History\Scheduler;
use SitePilotAI\Issues\IssueManager;
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
        add_action( 'wp_ajax_sitepilot_ai_fix_issue', array( $this, 'ajax_fix_issue' ) );
        add_action( 'admin_post_sitepilot_ai_save_history_settings', array( $this, 'save_history_settings' ) );
        add_action( 'admin_post_sitepilot_ai_clear_history', array( $this, 'clear_history' ) );
    }

    public function register_menu(): void {
        add_menu_page( __( 'SitePilot AI', 'sitepilot-ai' ), __( 'SitePilot AI', 'sitepilot-ai' ), 'manage_options', 'sitepilot-ai', array( $this, 'render_dashboard' ), 'dashicons-performance', 58 );
        add_submenu_page( 'sitepilot-ai', __( 'Dashboard', 'sitepilot-ai' ), __( 'Dashboard', 'sitepilot-ai' ), 'manage_options', 'sitepilot-ai', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'sitepilot-ai', __( 'Issues', 'sitepilot-ai' ), __( 'Issues', 'sitepilot-ai' ), 'manage_options', 'sitepilot-ai-issues', array( $this, 'render_issues' ) );
        add_submenu_page( 'sitepilot-ai', __( 'History', 'sitepilot-ai' ), __( 'History', 'sitepilot-ai' ), 'manage_options', 'sitepilot-ai-history', array( $this, 'render_history' ) );
    }

    public function enqueue_assets( string $hook_suffix ): void {
        if ( ! in_array( $hook_suffix, array( 'toplevel_page_sitepilot-ai', 'sitepilot-ai_page_sitepilot-ai-issues', 'sitepilot-ai_page_sitepilot-ai-history' ), true ) ) {
            return;
        }

        wp_enqueue_style( 'sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/css/admin.css', array(), SITEPILOT_AI_VERSION );
        wp_enqueue_script( 'sitepilot-ai-admin', SITEPILOT_AI_URL . 'assets/js/admin.js', array(), SITEPILOT_AI_VERSION, true );
        wp_localize_script( 'sitepilot-ai-admin', 'SitePilotAI', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'scanNonce'  => wp_create_nonce( 'sitepilot_ai_scan' ),
            'fixNonce'   => wp_create_nonce( 'sitepilot_ai_fix' ),
            'scanning'   => __( 'Scanning website…', 'sitepilot-ai' ),
            'completed'  => __( 'Website scan completed successfully.', 'sitepilot-ai' ),
            'fixing'     => __( 'Applying fix…', 'sitepilot-ai' ),
            'fixed'      => __( 'Fix applied. Running a fresh scan…', 'sitepilot-ai' ),
            'error'      => __( 'The request could not be completed.', 'sitepilot-ai' ),
            'enabled'    => __( 'Enabled', 'sitepilot-ai' ),
            'disabled'   => __( 'Disabled', 'sitepilot-ai' ),
            'confirmFix' => __( 'Apply this fix now?', 'sitepilot-ai' ),
        ) );
    }

    public function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $results    = $this->scanner->get_results();
        $history    = new HistoryRepository();
        $latest     = $history->latest();
        $previous   = $history->previous_to_latest();
        $score_delta = ( $latest && $previous ) ? (int) $latest['overall_score'] - (int) $previous['overall_score'] : null;
        $scan_count = $history->count();
        require SITEPILOT_AI_PATH . 'templates/dashboard.php';
    }

    public function render_issues(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $results      = $this->scanner->get_results();
        $manager      = new IssueManager();
        $all_issues   = $results['health']['issues'] ?? array();
        $severity     = isset( $_GET['severity'] ) ? sanitize_key( wp_unslash( $_GET['severity'] ) ) : '';
        $category     = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
        $issues       = $manager->filter( $all_issues, $severity, $category );
        $issue_counts = $manager->counts( $all_issues );
        $categories   = $manager->categories( $all_issues );
        require SITEPILOT_AI_PATH . 'templates/issues.php';
    }

    public function render_history(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $repository = new HistoryRepository();
        $history    = $repository->recent( 50 );
        $scan_count = $repository->count();
        $frequency  = (string) get_option( 'sitepilot_ai_scan_frequency', 'daily' );
        require SITEPILOT_AI_PATH . 'templates/history.php';
    }

    public function save_history_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Permission denied.', 'sitepilot-ai' ) );
        check_admin_referer( 'sitepilot_ai_history_settings' );
        $frequency = isset( $_POST['scan_frequency'] ) ? sanitize_key( wp_unslash( $_POST['scan_frequency'] ) ) : 'daily';
        if ( ! in_array( $frequency, array( 'disabled', 'daily', 'weekly', 'monthly' ), true ) ) $frequency = 'daily';
        Scheduler::reschedule( $frequency );
        wp_safe_redirect( add_query_arg( array( 'page' => 'sitepilot-ai-history', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function clear_history(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Permission denied.', 'sitepilot-ai' ) );
        check_admin_referer( 'sitepilot_ai_clear_history' );
        ( new HistoryRepository() )->delete_all();
        wp_safe_redirect( add_query_arg( array( 'page' => 'sitepilot-ai-history', 'cleared' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function ajax_run_scan(): void {
        check_ajax_referer( 'sitepilot_ai_scan', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sitepilot-ai' ) ), 403 );
        wp_send_json_success( $this->scanner->run_scan( true ) );
    }

    public function ajax_fix_issue(): void {
        check_ajax_referer( 'sitepilot_ai_fix', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sitepilot-ai' ) ), 403 );
        $action = isset( $_POST['fix_action'] ) ? sanitize_key( wp_unslash( $_POST['fix_action'] ) ) : '';
        $result = ( new FixManager() )->run( $action );
        if ( empty( $result['success'] ) ) wp_send_json_error( array( 'message' => $result['message'] ?? __( 'The fix failed.', 'sitepilot-ai' ) ), 400 );
        wp_send_json_success( array( 'message' => $result['message'], 'scan' => $this->scanner->run_scan( true ) ) );
    }
}
