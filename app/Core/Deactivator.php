<?php
namespace SitePilotAI\Core;

use SitePilotAI\History\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Deactivator {
    public static function deactivate(): void {
        delete_transient( 'sitepilot_ai_scan_results' );
        Scheduler::unschedule();
    }
}
