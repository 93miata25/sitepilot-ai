<?php
namespace SitePilotAI\Scanner;

use SitePilotAI\Issues\IssueManager;

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

        $add = static function( string $id, string $category, int $points, string $severity, string $title, string $description, string $fix, bool $fixable = false, string $fix_action = '' ) use ( &$categories, &$issues ): void {
            $categories[ $category ] = max( 0, $categories[ $category ] - $points );
            $issues[] = array(
                'id'          => $id,
                'category'    => $category,
                'severity'    => $severity,
                'title'       => $title,
                'description' => $description,
                'fix'         => $fix,
                'impact'      => $points,
                'fixable'     => $fixable,
                'fix_action'  => $fix_action,
            );
        };

        if ( empty( $scan['server']['https'] ) ) {
            $add( 'https_disabled', 'security', 30, 'critical', __( 'Enable HTTPS', 'sitepilot-ai' ), __( 'The website is not using an encrypted connection.', 'sitepilot-ai' ), __( 'Install an SSL certificate and redirect all HTTP traffic to HTTPS.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['debug_disabled'] ) ) {
            $add( 'wp_debug_enabled', 'security', 15, 'high', __( 'Disable WP_DEBUG', 'sitepilot-ai' ), __( 'Debug mode may expose technical details to visitors.', 'sitepilot-ai' ), __( 'Set WP_DEBUG to false in wp-config.php on production websites.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['file_editor_locked'] ) ) {
            $add( 'file_editor_enabled', 'security', 8, 'medium', __( 'Disable the file editor', 'sitepilot-ai' ), __( 'Administrators can modify plugin and theme files from WordPress.', 'sitepilot-ai' ), __( 'Define DISALLOW_FILE_EDIT as true in wp-config.php.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['security']['security_headers']['x-content-type-options'] ) ) {
            $add( 'missing_security_headers', 'security', 5, 'low', __( 'Add security headers', 'sitepilot-ai' ), __( 'The X-Content-Type-Options response header was not detected.', 'sitepilot-ai' ), __( 'Enable the recommended security headers.', 'sitepilot-ai' ), true, 'add_security_headers' );
        }
        if ( ! empty( $scan['security']['xmlrpc_enabled'] ) && ! get_option( 'sitepilot_ai_disable_xmlrpc' ) ) {
            $add( 'xmlrpc_enabled', 'security', 4, 'low', __( 'Disable XML-RPC when unused', 'sitepilot-ai' ), __( 'XML-RPC can increase the website attack surface when it is not required.', 'sitepilot-ai' ), __( 'Disable XML-RPC unless a service on this website depends on it.', 'sitepilot-ai' ), true, 'disable_xmlrpc' );
        }
        if ( empty( $scan['performance']['browser_cache'] ) ) {
            $add( 'browser_cache_missing', 'performance', 15, 'high', __( 'Enable browser caching', 'sitepilot-ai' ), __( 'A useful Cache-Control header was not detected on the homepage.', 'sitepilot-ai' ), __( 'Configure page or server caching with browser cache headers.', 'sitepilot-ai' ) );
        }
        if ( 'Not detected' === ( $scan['performance']['compression'] ?? '' ) ) {
            $add( 'compression_missing', 'performance', 15, 'high', __( 'Enable response compression', 'sitepilot-ai' ), __( 'GZIP or Brotli compression was not detected.', 'sitepilot-ai' ), __( 'Enable Brotli or GZIP compression in your server or CDN.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['performance']['object_cache'] ) ) {
            $add( 'object_cache_missing', 'performance', 8, 'medium', __( 'Consider persistent object caching', 'sitepilot-ai' ), __( 'WordPress is not using an external persistent object cache.', 'sitepilot-ai' ), __( 'Use Redis or Memcached when your hosting environment supports it.', 'sitepilot-ai' ) );
        }
        if ( (int) ( $scan['database']['autoload_bytes'] ?? 0 ) > 800000 ) {
            $add( 'autoload_options_large', 'performance', 10, 'high', __( 'Reduce autoloaded options', 'sitepilot-ai' ), __( 'A large amount of option data loads on every request.', 'sitepilot-ai' ), __( 'Audit large autoloaded options and remove stale plugin data.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['search_visible'] ) ) {
            $add( 'search_visibility_blocked', 'seo', 40, 'critical', __( 'Allow search engines', 'sitepilot-ai' ), __( 'WordPress is configured to discourage search engines.', 'sitepilot-ai' ), __( 'Enable search engine visibility under Settings → Reading.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['permalinks'] ) ) {
            $add( 'plain_permalinks', 'seo', 20, 'high', __( 'Use readable permalinks', 'sitepilot-ai' ), __( 'Plain numeric URLs are enabled.', 'sitepilot-ai' ), __( 'Choose a descriptive permalink structure under Settings → Permalinks.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['sitemap_available'] ) ) {
            $add( 'sitemap_missing', 'seo', 12, 'medium', __( 'Provide an XML sitemap', 'sitepilot-ai' ), __( 'The WordPress sitemap endpoint was not available.', 'sitepilot-ai' ), __( 'Enable the core sitemap or configure one through your SEO plugin.', 'sitepilot-ai' ) );
        }
        if ( empty( $scan['seo']['site_title'] ) ) {
            $add( 'site_title_missing', 'seo', 15, 'high', __( 'Set a site title', 'sitepilot-ai' ), __( 'The WordPress site title is empty.', 'sitepilot-ai' ), __( 'Add a clear site title under Settings → General.', 'sitepilot-ai' ) );
        }
        if ( ! empty( $scan['plugins']['update_count'] ) ) {
            $deduction = min( 35, (int) $scan['plugins']['update_count'] * 5 );
            $add( 'plugin_updates_available', 'updates', $deduction, 'high', __( 'Update plugins', 'sitepilot-ai' ), sprintf( _n( '%d plugin update is available.', '%d plugin updates are available.', (int) $scan['plugins']['update_count'], 'sitepilot-ai' ), (int) $scan['plugins']['update_count'] ), __( 'Back up the site, review compatibility, and install available updates.', 'sitepilot-ai' ) );
        }
        if ( ! empty( $scan['plugins']['inactive'] ) ) {
            $deduction = min( 15, (int) $scan['plugins']['inactive'] * 2 );
            $add( 'inactive_plugins', 'updates', $deduction, 'low', __( 'Review inactive plugins', 'sitepilot-ai' ), __( 'Inactive plugins still add maintenance and security exposure.', 'sitepilot-ai' ), __( 'Delete inactive plugins that are no longer needed.', 'sitepilot-ai' ) );
        }

        $issues = ( new IssueManager() )->prepare( $issues );
        $score  = (int) round(
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
            'counts'     => ( new IssueManager() )->counts( $issues ),
        );
    }
}
