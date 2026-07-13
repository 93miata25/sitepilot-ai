<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HealthScore {
    public function calculate( array $scan ): array {
        $categories = array(
            'performance'   => 100,
            'security'      => 100,
            'seo'           => 100,
            'accessibility' => 100,
            'updates'       => 100,
        );
        $issues = array();

        $add = static function( string $category, int $points, string $severity, string $title, string $reason, string $fix ) use ( &$categories, &$issues ): void {
            $categories[ $category ] = max( 0, $categories[ $category ] - $points );
            $issues[] = array(
                'category' => $category,
                'severity' => $severity,
                'title'    => $title,
                'reason'   => $reason,
                'fix'      => $fix,
                'impact'   => $points,
            );
        };

        if ( empty( $scan['server']['https'] ) ) {
            $add( 'security', 30, 'critical', __( 'Enable HTTPS', 'sitepilot-ai' ), __( 'The website is not using an encrypted connection.', 'sitepilot-ai' ), __( 'Install an SSL certificate and redirect all HTTP traffic to HTTPS.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['debug_disabled'] ) ) {
            $add( 'security', 15, 'high', __( 'Disable WP_DEBUG', 'sitepilot-ai' ), __( 'Debug mode may expose technical details to visitors.', 'sitepilot-ai' ), __( 'Set WP_DEBUG to false in wp-config.php on production websites.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['file_editor_locked'] ) ) {
            $add( 'security', 8, 'medium', __( 'Disable the file editor', 'sitepilot-ai' ), __( 'Administrators can modify plugin and theme files from WordPress.', 'sitepilot-ai' ), __( 'Define DISALLOW_FILE_EDIT as true in wp-config.php.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['security_headers']['x-content-type-options'] ) ) {
            $add( 'security', 5, 'low', __( 'Add X-Content-Type-Options', 'sitepilot-ai' ), __( 'This response header was not detected.', 'sitepilot-ai' ), __( 'Send the header X-Content-Type-Options: nosniff from the web server.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['performance']['browser_cache'] ) ) {
            $add( 'performance', 15, 'high', __( 'Enable browser caching', 'sitepilot-ai' ), __( 'A useful Cache-Control header was not detected on the homepage.', 'sitepilot-ai' ), __( 'Configure page or server caching with browser cache headers.', 'sitepilot-ai' ) );
        }
        if ( 'Not detected' === ( $scan['performance']['compression'] ?? '' ) ) {
            $add( 'performance', 15, 'high', __( 'Enable response compression', 'sitepilot-ai' ), __( 'GZIP or Brotli compression was not detected.', 'sitepilot-ai' ), __( 'Enable Brotli or GZIP compression in your server or CDN.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['performance']['object_cache'] ) ) {
            $add( 'performance', 8, 'medium', __( 'Consider persistent object caching', 'sitepilot-ai' ), __( 'WordPress is not using an external persistent object cache.', 'sitepilot-ai' ), __( 'Use Redis or Memcached when your hosting environment supports it.', 'sitepilot-ai' ) );
        }
        if ( (int) ( $scan['database']['autoload_bytes'] ?? 0 ) > 800000 ) {
            $add( 'performance', 10, 'high', __( 'Reduce autoloaded options', 'sitepilot-ai' ), __( 'A large amount of option data loads on every request.', 'sitepilot-ai' ), __( 'Audit large autoloaded options and remove stale plugin data.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['search_visible'] ) ) {
            $add( 'seo', 40, 'critical', __( 'Allow search engines', 'sitepilot-ai' ), __( 'WordPress is configured to discourage search engines.', 'sitepilot-ai' ), __( 'Enable search engine visibility under Settings → Reading.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['permalinks'] ) ) {
            $add( 'seo', 20, 'high', __( 'Use readable permalinks', 'sitepilot-ai' ), __( 'Plain numeric URLs are enabled.', 'sitepilot-ai' ), __( 'Choose a descriptive permalink structure under Settings → Permalinks.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['sitemap_available'] ) ) {
            $add( 'seo', 12, 'medium', __( 'Provide an XML sitemap', 'sitepilot-ai' ), __( 'The WordPress sitemap endpoint was not available.', 'sitepilot-ai' ), __( 'Enable the core sitemap or configure one through your SEO plugin.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['site_title'] ) ) {
            $add( 'seo', 15, 'high', __( 'Set a site title', 'sitepilot-ai' ), __( 'The WordPress site title is empty.', 'sitepilot-ai' ), __( 'Add a clear site title under Settings → General.', 'sitepilot-ai' ) );
        }
        if ( ! empty( $scan['plugins']['update_count'] ) ) {
            $deduction = min( 35, (int) $scan['plugins']['update_count'] * 5 );
            $add( 'updates', $deduction, 'high', __( 'Update plugins', 'sitepilot-ai' ), sprintf( _n( '%d plugin update is available.', '%d plugin updates are available.', (int) $scan['plugins']['update_count'], 'sitepilot-ai' ), (int) $scan['plugins']['update_count'] ), __( 'Back up the site, review compatibility, and install available updates.', 'sitepilot-ai' ) );
        }
        if ( ! empty( $scan['plugins']['inactive'] ) ) {
            $deduction = min( 15, (int) $scan['plugins']['inactive'] * 2 );
            $add( 'updates', $deduction, 'low', __( 'Review inactive plugins', 'sitepilot-ai' ), __( 'Inactive plugins still add maintenance and security exposure.', 'sitepilot-ai' ), __( 'Delete inactive plugins that are no longer needed.', 'sitepilot-ai' ) );
        }

        $categories['accessibility'] = 100; // DOM-level accessibility auditing is added in a later milestone.
        $score = (int) round(
            ( $categories['performance'] * 0.25 ) +
            ( $categories['security'] * 0.30 ) +
            ( $categories['seo'] * 0.20 ) +
            ( $categories['accessibility'] * 0.10 ) +
            ( $categories['updates'] * 0.15 )
        );

        if ( $score >= 90 ) {
            $label = __( 'Excellent', 'sitepilot-ai' );
        } elseif ( $score >= 75 ) {
            $label = __( 'Good', 'sitepilot-ai' );
        } elseif ( $score >= 50 ) {
            $label = __( 'Needs attention', 'sitepilot-ai' );
        } else {
            $label = __( 'Critical', 'sitepilot-ai' );
        }

        return array(
            'score'      => max( 0, min( 100, $score ) ),
            'label'      => $label,
            'categories' => $categories,
            'issues'     => $issues,
        );
    }
}
