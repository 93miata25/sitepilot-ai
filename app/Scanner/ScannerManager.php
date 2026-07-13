<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class ScannerManager
{
    private const CACHE_KEY = 'sitepilot_ai_scan';

    public function run(bool $force = false): array
    {
        if (! $force) {
            $cached = get_transient(self::CACHE_KEY);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $started = microtime(true);
        $sections = array(
            'wordpress' => (new WordPressScanner())->scan(),
            'server'    => (new ServerScanner())->scan(),
            'theme'     => (new ThemeScanner())->scan(),
            'plugins'   => (new PluginScanner())->scan(),
            'security'  => (new SecurityScanner())->scan(),
        );

        $score = HealthScore::calculate($sections);
        $result = array(
            'generated_at' => current_time('mysql'),
            'duration_ms'  => (int) round((microtime(true) - $started) * 1000),
            'score'        => $score['score'],
            'status'       => $score['status'],
            'issues'       => $score['issues'],
            'sections'     => $sections,
        );

        set_transient(self::CACHE_KEY, $result, HOUR_IN_SECONDS);
        update_option('sitepilot_ai_last_scan', $result, false);

        return $result;
    }
}
