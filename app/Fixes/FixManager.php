<?php
namespace SitePilotAI\Fixes;

use SitePilotAI\Scanner\ScannerManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FixManager {
    public function run( string $action ): array {
        $action   = sanitize_key( $action );
        $registry = new FixRegistry();
        $fix      = $registry->get( $action );

        if ( ! $fix ) {
            return array(
                'success' => false,
                'message' => __( 'This fix is not registered.', 'sitepilot-ai' ),
            );
        }

        $scanner      = new ScannerManager();
        $before_scan  = $scanner->get_results();
        $score_before = (int) ( $before_scan['health']['score'] ?? 0 );
        $details      = '';

        switch ( $action ) {
            case 'disable_xmlrpc':
                update_option( 'sitepilot_ai_disable_xmlrpc', 1, false );
                $message = __( 'XML-RPC has been disabled by SitePilot AI.', 'sitepilot-ai' );
                break;

            case 'add_security_headers':
                update_option( 'sitepilot_ai_security_headers', 1, false );
                $message = __( 'Recommended security headers have been enabled.', 'sitepilot-ai' );
                break;

            case 'delete_expired_transients':
                $deleted = $this->delete_expired_transients();
                $message = sprintf(
                    _n( '%d expired transient was removed.', '%d expired transients were removed.', $deleted, 'sitepilot-ai' ),
                    $deleted
                );
                $details = sprintf( 'Deleted records: %d', $deleted );
                break;

            default:
                return array(
                    'success' => false,
                    'message' => __( 'This issue does not have an automatic fix yet.', 'sitepilot-ai' ),
                );
        }

        $scanner->clear_cache();
        $after_scan  = $scanner->run_scan( true );
        $score_after = (int) ( $after_scan['health']['score'] ?? $score_before );

        ( new ActivityRepository() )->add(
            array(
                'action_id'    => $action,
                'title'        => $fix['title'],
                'status'       => 'success',
                'score_before' => $score_before,
                'score_after'  => $score_after,
                'details'      => $details ?: $message,
            )
        );

        return array(
            'success'      => true,
            'message'      => $message,
            'score_before' => $score_before,
            'score_after'  => $score_after,
            'scan'         => $after_scan,
        );
    }

    private function delete_expired_transients(): int {
        global $wpdb;

        $timeout_like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
        $timeouts     = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $timeout_like,
                time()
            )
        );

        $deleted = 0;
        foreach ( $timeouts as $timeout_name ) {
            $transient = substr( $timeout_name, strlen( '_transient_timeout_' ) );
            if ( delete_transient( $transient ) ) {
                ++$deleted;
            }
        }

        return $deleted;
    }
}
