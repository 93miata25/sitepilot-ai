<?php
namespace SitePilotAI\Scanner;

if (! defined('ABSPATH')) {
    exit;
}

final class HealthScore
{
    public static function calculate(array $sections): array
    {
        $score = 100;
        $issues = array();
        $security = $sections['security'];
        $server = $sections['server'];
        $plugins = $sections['plugins'];
        $wordpress = $sections['wordpress'];

        self::deduct($score, $issues, ! $security['https'], 20, 'high', 'HTTPS is not enabled', 'Enable SSL and force HTTPS for all visitors.');
        self::deduct($score, $issues, ! $security['debug_disabled'], 10, 'high', 'WordPress debug mode is enabled', 'Disable WP_DEBUG on production sites.');
        self::deduct($score, $issues, ! $security['file_editor_off'], 5, 'medium', 'Theme and plugin file editing is enabled', 'Set DISALLOW_FILE_EDIT to true in wp-config.php.');
        self::deduct($score, $issues, $security['default_admin'], 10, 'high', 'The default admin username exists', 'Create a different administrator account and remove the admin username.');
        self::deduct($score, $issues, $plugins['updates'] > 0, min(15, $plugins['updates'] * 3), 'medium', 'Plugin updates are available', 'Review and install available plugin updates after taking a backup.');
        self::deduct($score, $issues, ! $wordpress['permalink_set'], 5, 'medium', 'Plain permalinks are enabled', 'Choose a descriptive permalink structure under Settings → Permalinks.');
        self::deduct($score, $issues, ! $wordpress['search_visibility'], 15, 'high', 'Search engines are discouraged from indexing the site', 'Enable search visibility when the site is ready to be public.');
        self::deduct($score, $issues, ! $server['opcache'], 4, 'low', 'PHP OPcache is not detected', 'Enable OPcache at the server level for faster PHP execution.');
        self::deduct($score, $issues, ! $server['object_cache'], 3, 'low', 'Persistent object caching is not detected', 'Consider Redis or Memcached on sites that benefit from persistent object caching.');

        $score = max(0, min(100, $score));

        return array(
            'score'  => $score,
            'status' => $score >= 90 ? 'Excellent' : ($score >= 75 ? 'Good' : ($score >= 60 ? 'Needs attention' : 'Poor')),
            'issues' => $issues,
        );
    }

    private static function deduct(int &$score, array &$issues, bool $condition, int $points, string $severity, string $title, string $fix): void
    {
        if (! $condition) {
            return;
        }

        $score -= $points;
        $issues[] = array(
            'severity' => $severity,
            'title'    => $title,
            'fix'      => $fix,
            'impact'   => $points,
        );
    }
}
