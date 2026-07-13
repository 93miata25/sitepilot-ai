<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class WordPressScanner implements ScannerInterface {
    public function scan(): array {
        global $wp_version;

        return array(
            'version'    => $wp_version,
            'multisite'  => is_multisite(),
            'language'   => get_locale(),
            'timezone'   => wp_timezone_string(),
            'permalinks' => '' !== (string) get_option( 'permalink_structure', '' ),
            'site_url'   => site_url(),
            'home_url'   => home_url(),
        );
    }
}
