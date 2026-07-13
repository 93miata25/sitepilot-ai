<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PluginScanner implements ScannerInterface {
    public function scan(): array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = (array) get_option( 'active_plugins', array() );
        $updates        = get_site_transient( 'update_plugins' );
        $update_count   = is_object( $updates ) && isset( $updates->response ) ? count( $updates->response ) : 0;

        if ( is_multisite() ) {
            $network_active = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
            $active_plugins = array_unique( array_merge( $active_plugins, $network_active ) );
        }

        return array(
            'installed'     => count( $all_plugins ),
            'active'        => count( $active_plugins ),
            'inactive'      => max( 0, count( $all_plugins ) - count( $active_plugins ) ),
            'update_count'  => $update_count,
        );
    }
}
