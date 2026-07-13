<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ScannerManager {
    private const TRANSIENT_KEY = 'sitepilot_ai_scan_results';
    private const CACHE_SECONDS = 15 * MINUTE_IN_SECONDS;

    public function get_results(): array {
        $cached = get_transient( self::TRANSIENT_KEY );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        return $this->run_scan();
    }

    public function run_scan( bool $force = false ): array {
        if ( ! $force ) {
            $cached = get_transient( self::TRANSIENT_KEY );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $results = array(
            'wordpress' => ( new WordPressScanner() )->scan(),
            'server'    => ( new ServerScanner() )->scan(),
            'theme'     => ( new ThemeScanner() )->scan(),
            'plugins'   => ( new PluginScanner() )->scan(),
            'content'   => ( new ContentScanner() )->scan(),
            'security'  => ( new SecurityScanner() )->scan(),
            'scanned_at'=> current_time( 'mysql' ),
        );

        $results['health'] = ( new HealthScore() )->calculate( $results );

        set_transient( self::TRANSIENT_KEY, $results, self::CACHE_SECONDS );

        return $results;
    }
}
