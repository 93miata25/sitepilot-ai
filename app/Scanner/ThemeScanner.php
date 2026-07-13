<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class ThemeScanner
{
    public function scan(): array
    {
        $theme = wp_get_theme();

        return array(
            'name'         => $theme->get('Name') ?: $theme->get_stylesheet(),
            'version'      => $theme->get('Version') ?: 'Unknown',
            'parent_theme' => $theme->parent() ? $theme->parent()->get('Name') : '',
            'stylesheet'   => $theme->get_stylesheet(),
        );
    }
}
