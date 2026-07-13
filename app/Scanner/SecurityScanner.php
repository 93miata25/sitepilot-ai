<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SecurityScanner implements ScannerInterface {
    public function scan(): array {
        return array(
            'https'                 => is_ssl(),
            'debug_disabled'        => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
            'debug_display_disabled'=> ! ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ),
            'file_editor_locked'    => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
            'database_prefix'       => $this->has_custom_database_prefix(),
            'users_can_register'    => (bool) get_option( 'users_can_register', false ),
            'xmlrpc_enabled'        => (bool) apply_filters( 'xmlrpc_enabled', true ),
            'rest_api_available'    => function_exists( 'rest_get_server' ),
            'wp_config_permissions' => $this->permission_label( ABSPATH . 'wp-config.php' ),
            'htaccess_permissions'  => $this->permission_label( ABSPATH . '.htaccess' ),
            'security_headers'      => $this->security_headers(),
        );
    }

    private function has_custom_database_prefix(): bool {
        global $wpdb;
        return 'wp_' !== $wpdb->prefix;
    }

    private function permission_label( string $path ): string {
        if ( ! file_exists( $path ) ) {
            return __( 'Not found', 'sitepilot-ai' );
        }
        $perms = @fileperms( $path );
        return false === $perms ? __( 'Unknown', 'sitepilot-ai' ) : substr( sprintf( '%o', $perms ), -4 );
    }

    private function security_headers(): array {
        $response = wp_remote_head( home_url( '/' ), array( 'timeout' => 6, 'redirection' => 3 ) );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        $headers = wp_remote_retrieve_headers( $response );
        $wanted  = array(
            'strict-transport-security',
            'content-security-policy',
            'x-content-type-options',
            'x-frame-options',
            'referrer-policy',
            'permissions-policy',
        );
        $result = array();
        foreach ( $wanted as $name ) {
            $result[ $name ] = isset( $headers[ $name ] ) && '' !== (string) $headers[ $name ];
        }
        return $result;
    }
}
