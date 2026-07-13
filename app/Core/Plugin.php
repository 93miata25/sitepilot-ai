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

        if ( is_admin() ) {
            new Admin();
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'sitepilot-ai',
            false,
            dirname( plugin_basename( SITEPILOT_AI_FILE ) ) . '/languages'
        );
    }
}
