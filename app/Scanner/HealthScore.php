<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HealthScore {
    public function calculate( array $scan ): array {
        $score  = 100;
        $issues = array();

        if ( empty( $scan['server']['https'] ) ) {
            $score   -= 25;
            $issues[] = array( 'severity' => 'critical', 'message' => __( 'Enable HTTPS for the entire website.', 'sitepilot-ai' ) );
        }

        if ( empty( $scan['security']['debug_disabled'] ) ) {
            $score   -= 15;
            $issues[] = array( 'severity' => 'warning', 'message' => __( 'Disable WP_DEBUG on the production website.', 'sitepilot-ai' ) );
        }

        if ( empty( $scan['security']['file_editor_locked'] ) ) {
            $score   -= 5;
            $issues[] = array( 'severity' => 'info', 'message' => __( 'Disable the built-in plugin and theme file editor.', 'sitepilot-ai' ) );
        }

        if ( ! empty( $scan['plugins']['update_count'] ) ) {
            $deduction = min( 20, (int) $scan['plugins']['update_count'] * 3 );
            $score    -= $deduction;
            $issues[]  = array(
                'severity' => 'warning',
                'message'  => sprintf(
                    _n( '%d plugin update is available.', '%d plugin updates are available.', (int) $scan['plugins']['update_count'], 'sitepilot-ai' ),
                    (int) $scan['plugins']['update_count']
                ),
            );
        }

        if ( empty( $scan['wordpress']['permalinks'] ) ) {
            $score   -= 10;
            $issues[] = array( 'severity' => 'warning', 'message' => __( 'Configure readable permalinks instead of plain URLs.', 'sitepilot-ai' ) );
        }

        if ( ! empty( $scan['security']['users_can_register'] ) ) {
            $score   -= 5;
            $issues[] = array( 'severity' => 'info', 'message' => __( 'Review whether public user registration is necessary.', 'sitepilot-ai' ) );
        }

        $score = max( 0, min( 100, $score ) );

        if ( $score >= 90 ) {
            $label = __( 'Excellent', 'sitepilot-ai' );
        } elseif ( $score >= 75 ) {
            $label = __( 'Good', 'sitepilot-ai' );
        } elseif ( $score >= 50 ) {
            $label = __( 'Needs attention', 'sitepilot-ai' );
        } else {
            $label = __( 'Critical', 'sitepilot-ai' );
        }

        return array(
            'score'  => $score,
            'label'  => $label,
            'issues' => $issues,
        );
    }
}
