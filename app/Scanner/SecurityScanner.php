<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SecurityScanner implements ScannerInterface {
    public function scan(): array {
        return array(
            'https'              => is_ssl(),
            'debug_disabled'     => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
            'file_editor_locked' => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
            'database_prefix'    => $this->has_custom_database_prefix(),
            'users_can_register' => (bool) get_option( 'users_can_register', false ),
        );
    }

    private function has_custom_database_prefix(): bool {
        global $wpdb;
        return 'wp_' !== $wpdb->prefix;
    }
}
