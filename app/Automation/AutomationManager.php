<?php
namespace SitePilotAI\Automation;

use SitePilotAI\Fixes\ActivityRepository;
use SitePilotAI\Modules\Database\DatabaseOptimizer;
use SitePilotAI\Scanner\ScannerManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AutomationManager {
    public const DATABASE_HOOK = 'sitepilot_ai_automated_database_cleanup';

    public function register(): void {
        add_action( self::DATABASE_HOOK, array( $this, 'run_database_cleanup' ) );
    }

    public static function schedule(): void {
        $frequency = (string) get_option( 'sitepilot_ai_database_frequency', 'weekly' );
        if ( 'disabled' === $frequency ) {
            self::unschedule_database();
            return;
        }
        if ( wp_next_scheduled( self::DATABASE_HOOK ) ) {
            return;
        }
        $recurrence = 'monthly' === $frequency ? 'sitepilot_monthly' : 'sitepilot_weekly';
        wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, self::DATABASE_HOOK );
    }

    public static function reschedule_database( string $frequency ): void {
        if ( ! in_array( $frequency, array( 'disabled', 'weekly', 'monthly' ), true ) ) {
            $frequency = 'weekly';
        }
        self::unschedule_database();
        update_option( 'sitepilot_ai_database_frequency', $frequency );
        self::schedule();
    }

    public static function unschedule_database(): void {
        $timestamp = wp_next_scheduled( self::DATABASE_HOOK );
        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::DATABASE_HOOK );
            $timestamp = wp_next_scheduled( self::DATABASE_HOOK );
        }
    }

    public function run_database_cleanup(): array {
        $tasks = get_option( 'sitepilot_ai_database_tasks', array( 'expired_transients', 'spam_comments', 'trashed_comments', 'trashed_posts', 'optimize_tables' ) );
        if ( ! is_array( $tasks ) || empty( $tasks ) ) {
            $tasks = array( 'expired_transients', 'optimize_tables' );
        }

        $result    = ( new DatabaseOptimizer() )->run( $tasks );
        $processed = array_sum( array_map( 'intval', $result['deleted'] ?? array() ) );
        $status    = empty( $result['errors'] ) ? 'success' : 'warning';
        $details   = sprintf(
            __( 'Processed %1$d items and reclaimed %2$s.', 'sitepilot-ai' ),
            $processed,
            (string) ( $result['reclaimed_size'] ?? '0 B' )
        );

        ( new ActivityRepository() )->add(
            array(
                'action_id' => 'automated_database_cleanup',
                'title'     => __( 'Automated database maintenance', 'sitepilot-ai' ),
                'status'    => $status,
                'details'   => $details,
            )
        );

        update_option( 'sitepilot_ai_database_last_run', current_time( 'mysql' ) );
        update_option( 'sitepilot_ai_database_last_result', $result );
        delete_transient( 'sitepilot_ai_scan_results' );

        return $result;
    }

    public function run_job( string $job ): array {
        if ( 'health_scan' === $job ) {
            $result = ( new ScannerManager() )->run_scan( true );
            update_option( 'sitepilot_ai_automation_last_scan', current_time( 'mysql' ) );
            ( new ActivityRepository() )->add(
                array(
                    'action_id'   => 'manual_automation_scan',
                    'title'       => __( 'Manual health automation', 'sitepilot-ai' ),
                    'status'      => 'success',
                    'score_after' => (int) ( $result['health']['score'] ?? 0 ),
                    'details'     => __( 'A complete website health scan was run from the Automation Center.', 'sitepilot-ai' ),
                )
            );
            return array(
                'success' => true,
                'message' => __( 'Website health scan completed.', 'sitepilot-ai' ),
                'score'   => (int) ( $result['health']['score'] ?? 0 ),
            );
        }

        if ( 'database_cleanup' === $job ) {
            $result = $this->run_database_cleanup();
            return array(
                'success'   => empty( $result['errors'] ),
                'message'   => empty( $result['errors'] ) ? __( 'Database maintenance completed.', 'sitepilot-ai' ) : __( 'Database maintenance completed with warnings.', 'sitepilot-ai' ),
                'reclaimed' => (string) ( $result['reclaimed_size'] ?? '0 B' ),
            );
        }

        return array( 'success' => false, 'message' => __( 'Unknown automation job.', 'sitepilot-ai' ) );
    }

    public function status(): array {
        return array(
            'scan_frequency'       => (string) get_option( 'sitepilot_ai_scan_frequency', 'daily' ),
            'database_frequency'   => (string) get_option( 'sitepilot_ai_database_frequency', 'weekly' ),
            'database_tasks'       => (array) get_option( 'sitepilot_ai_database_tasks', array( 'expired_transients', 'spam_comments', 'trashed_comments', 'trashed_posts', 'optimize_tables' ) ),
            'next_scan'            => wp_next_scheduled( \SitePilotAI\History\Scheduler::HOOK ) ?: 0,
            'next_database'        => wp_next_scheduled( self::DATABASE_HOOK ) ?: 0,
            'database_last_run'    => (string) get_option( 'sitepilot_ai_database_last_run', '' ),
            'automation_last_scan' => (string) get_option( 'sitepilot_ai_automation_last_scan', '' ),
        );
    }
}
