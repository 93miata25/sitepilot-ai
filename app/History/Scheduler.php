<?php
namespace SitePilotAI\History;

use SitePilotAI\Scanner\ScannerManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Scheduler {
    public const HOOK = 'sitepilot_ai_scheduled_scan';

    public function register(): void {
        add_filter( 'cron_schedules', array( $this, 'add_intervals' ) );
        add_action( self::HOOK, array( $this, 'run' ) );
    }

    public function add_intervals( array $schedules ): array {
        $schedules['sitepilot_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display'  => __( 'Once Weekly', 'sitepilot-ai' ),
        );
        $schedules['sitepilot_monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __( 'Once Monthly', 'sitepilot-ai' ),
        );
        return $schedules;
    }

    public static function schedule(): void {
        if ( wp_next_scheduled( self::HOOK ) ) {
            return;
        }

        $frequency = get_option( 'sitepilot_ai_scan_frequency', 'daily' );
        if ( 'disabled' === $frequency ) {
            return;
        }

        $recurrence = self::recurrence_for( $frequency );
        wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, self::HOOK );
    }

    public static function reschedule( string $frequency ): void {
        self::unschedule();
        update_option( 'sitepilot_ai_scan_frequency', $frequency );
        self::schedule();
    }

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled( self::HOOK );
        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
            $timestamp = wp_next_scheduled( self::HOOK );
        }
    }

    public function run(): void {
        ( new ScannerManager() )->run_scan( true );
    }

    private static function recurrence_for( string $frequency ): string {
        if ( 'weekly' === $frequency ) {
            return 'sitepilot_weekly';
        }
        if ( 'monthly' === $frequency ) {
            return 'sitepilot_monthly';
        }
        return 'daily';
    }
}
