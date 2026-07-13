<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'sitepilot_ai_installed_at' );
delete_option( 'sitepilot_ai_version' );
delete_transient( 'sitepilot_ai_scan_results' );
