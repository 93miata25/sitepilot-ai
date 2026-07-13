<?php
namespace SitePilotAI\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Activator
{
    public static function activate(): void
    {
        if (get_option('sitepilot_ai_installed_at') === false) {
            add_option('sitepilot_ai_installed_at', time(), '', false);
        }

        update_option('sitepilot_ai_version', SITEPILOT_AI_VERSION, false);
    }
}
