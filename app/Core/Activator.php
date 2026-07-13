<?php
namespace SitePilotAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Activator {
    public static function activate(): void {
        if ( false === get_option( 'sitepilot_ai_installed_at', false ) ) {
            add_option( 'sitepilot_ai_installed_at', current_time( 'mysql' ) );
        }

        update_option( 'sitepilot_ai_version', SITEPILOT_AI_VERSION );
        delete_transient( 'sitepilot_ai_scan_results' );
    }
}
