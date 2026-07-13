<?php
namespace SitePilotAI\Core;

use SitePilotAI\Fixes\ActivityRepository;
use SitePilotAI\History\HistoryRepository;
use SitePilotAI\History\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Activator {
    public static function activate(): void {
        if ( false === get_option( 'sitepilot_ai_installed_at', false ) ) {
            add_option( 'sitepilot_ai_installed_at', current_time( 'mysql' ) );
        }

        if ( false === get_option( 'sitepilot_ai_scan_frequency', false ) ) {
            add_option( 'sitepilot_ai_scan_frequency', 'daily' );
        }

        HistoryRepository::create_table();
        ActivityRepository::create_table();
        Scheduler::schedule();

        update_option( 'sitepilot_ai_version', SITEPILOT_AI_VERSION );
        delete_transient( 'sitepilot_ai_scan_results' );
    }
}
