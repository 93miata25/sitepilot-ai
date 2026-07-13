<?php
namespace SitePilotAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Autoloader {
    public static function register(): void {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    private static function autoload( string $class ): void {
        $prefix = 'SitePilotAI\\';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative = substr( $class, strlen( $prefix ) );
        $file     = SITEPILOT_AI_PATH . 'app/' . str_replace( '\\', '/', $relative ) . '.php';

        if ( is_readable( $file ) ) {
            require_once $file;
        }
    }
}
