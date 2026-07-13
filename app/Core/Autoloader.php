<?php
namespace SitePilotAI\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX = 'SitePilotAI\\';

    public static function register(): void
    {
        spl_autoload_register(array(self::class, 'autoload'));
    }

    private static function autoload(string $class): void
    {
        if (strpos($class, self::PREFIX) !== 0) {
            return;
        }

        $relative = substr($class, strlen(self::PREFIX));
        $file = SITEPILOT_AI_PATH . 'app/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
