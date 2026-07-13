<?php
namespace SitePilotAI\Fixes;

use SitePilotAI\Scanner\ScannerManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FixManager {
    public function run( string $action ): array {
        $action = sanitize_key( $action );

        switch ( $action ) {
            case 'disable_xmlrpc':
                update_option( 'sitepilot_ai_disable_xmlrpc', 1, false );
                $message = __( 'XML-RPC has been disabled by SitePilot AI.', 'sitepilot-ai' );
                break;

            case 'add_security_headers':
                update_option( 'sitepilot_ai_security_headers', 1, false );
                $message = __( 'Recommended security headers have been enabled.', 'sitepilot-ai' );
                break;

            default:
                return array(
                    'success' => false,
                    'message' => __( 'This issue does not have an automatic fix yet.', 'sitepilot-ai' ),
                );
        }

        ( new ScannerManager() )->clear_cache();

        return array(
            'success' => true,
            'message' => $message,
        );
    }
}
