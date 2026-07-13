<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ThemeScanner implements ScannerInterface {
    public function scan(): array {
        $theme  = wp_get_theme();
        $parent = $theme->parent();

        return array(
            'name'           => $theme->get( 'Name' ),
            'version'        => $theme->get( 'Version' ),
            'template'       => $theme->get_template(),
            'stylesheet'     => $theme->get_stylesheet(),
            'is_child_theme' => (bool) $parent,
            'parent_name'    => $parent ? $parent->get( 'Name' ) : '',
        );
    }
}
