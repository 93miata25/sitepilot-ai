<?php
namespace SitePilotAI\Fixes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FixRegistry {
    public function all(): array {
        return array(
            'disable_xmlrpc' => array(
                'id'          => 'disable_xmlrpc',
                'title'       => __( 'Disable XML-RPC', 'sitepilot-ai' ),
                'description' => __( 'Reduces the attack surface when no service on this website depends on XML-RPC.', 'sitepilot-ai' ),
                'category'    => 'security',
                'risk'        => 'low',
                'minutes'     => 1,
                'reversible'  => true,
            ),
            'add_security_headers' => array(
                'id'          => 'add_security_headers',
                'title'       => __( 'Enable security headers', 'sitepilot-ai' ),
                'description' => __( 'Adds recommended browser security headers to WordPress responses.', 'sitepilot-ai' ),
                'category'    => 'security',
                'risk'        => 'low',
                'minutes'     => 1,
                'reversible'  => true,
            ),
            'delete_expired_transients' => array(
                'id'          => 'delete_expired_transients',
                'title'       => __( 'Delete expired transients', 'sitepilot-ai' ),
                'description' => __( 'Removes expired temporary database records that WordPress no longer needs.', 'sitepilot-ai' ),
                'category'    => 'performance',
                'risk'        => 'low',
                'minutes'     => 1,
                'reversible'  => false,
            ),
        );
    }

    public function get( string $id ): ?array {
        $fixes = $this->all();
        return $fixes[ sanitize_key( $id ) ] ?? null;
    }

    public function for_issues( array $issues ): array {
        $fixes = $this->all();
        $items = array();

        foreach ( $issues as $issue ) {
            $action = sanitize_key( (string) ( $issue['fix_action'] ?? '' ) );
            if ( ! $action || empty( $fixes[ $action ] ) ) {
                continue;
            }

            $item           = $fixes[ $action ];
            $item['impact'] = max( 0, (int) ( $issue['impact'] ?? 0 ) );
            $item['severity'] = sanitize_key( (string) ( $issue['severity'] ?? 'low' ) );
            $item['issue_id'] = sanitize_key( (string) ( $issue['id'] ?? '' ) );
            $items[]        = $item;
        }

        usort(
            $items,
            static function ( array $left, array $right ): int {
                return (int) $right['impact'] <=> (int) $left['impact'];
            }
        );

        return $items;
    }
}
