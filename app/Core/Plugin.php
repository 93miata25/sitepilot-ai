<?php
namespace SitePilotAI\Core;

use SitePilotAI\Admin\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
        load_plugin_textdomain('sitepilot-ai', false, dirname(plugin_basename(SITEPILOT_AI_FILE)) . '/languages');

        if (is_admin()) {
            (new Admin())->register();
        }
    }

    private function __construct()
    {
    }
}
