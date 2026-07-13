<?php
namespace SitePilotAI\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Deactivator
{
    public static function deactivate(): void
    {
        delete_transient('sitepilot_ai_scan');
    }
}
