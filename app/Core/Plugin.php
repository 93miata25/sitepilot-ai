<?php
namespace SitePilotAI\Core;

use SitePilotAI\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {
    private static ?Plugin $instance = null;
    private bool $booted = false;

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function run(): void {
        if ( $this->booted ) {
            return;
        }

        $this->booted = true;

        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_filter( 'xmlrpc_enabled', array( $this, 'maybe_disable_xmlrpc' ) );
        add_action( 'send_headers', array( $this, 'maybe_send_security_headers' ) );

        if ( is_admin() ) {
            new Admin();
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'sitepilot-ai', false, dirname( plugin_basename( SITEPILOT_AI_FILE ) ) . '/languages' );
    }

    public function maybe_disable_xmlrpc( bool $enabled ): bool {
        return get_option( 'sitepilot_ai_disable_xmlrpc' ) ? false : $enabled;
    }

    public function maybe_send_security_headers(): void {
        if ( ! get_option( 'sitepilot_ai_security_headers' ) || headers_sent() ) {
            return;
        }

        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
    }
}
