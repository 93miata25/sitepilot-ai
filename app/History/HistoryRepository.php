<?php
namespace SitePilotAI\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HistoryRepository {
    public const TABLE_SUFFIX = 'sitepilot_ai_scans';

    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function create_table(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scanned_at datetime NOT NULL,
            overall_score smallint(3) unsigned NOT NULL DEFAULT 0,
            performance_score smallint(3) unsigned NOT NULL DEFAULT 0,
            security_score smallint(3) unsigned NOT NULL DEFAULT 0,
            seo_score smallint(3) unsigned NOT NULL DEFAULT 0,
            accessibility_score smallint(3) unsigned NOT NULL DEFAULT 0,
            updates_score smallint(3) unsigned NOT NULL DEFAULT 0,
            critical_issues int(10) unsigned NOT NULL DEFAULT 0,
            high_issues int(10) unsigned NOT NULL DEFAULT 0,
            medium_issues int(10) unsigned NOT NULL DEFAULT 0,
            low_issues int(10) unsigned NOT NULL DEFAULT 0,
            issue_count int(10) unsigned NOT NULL DEFAULT 0,
            scan_duration decimal(8,2) unsigned NOT NULL DEFAULT 0,
            database_size_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
            plugin_count int(10) unsigned NOT NULL DEFAULT 0,
            plugin_version varchar(32) NOT NULL DEFAULT '',
            snapshot longtext NULL,
            PRIMARY KEY  (id),
            KEY scanned_at (scanned_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    public function save( array $results ): int {
        global $wpdb;

        $health     = $results['health'] ?? array();
        $categories = $health['categories'] ?? array();
        $issues     = $health['issues'] ?? array();
        $counts     = array( 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0 );

        foreach ( $issues as $issue ) {
            $severity = isset( $issue['severity'] ) ? sanitize_key( (string) $issue['severity'] ) : 'low';
            if ( isset( $counts[ $severity ] ) ) {
                ++$counts[ $severity ];
            }
        }

        $database_bytes = isset( $results['database']['size_bytes'] ) ? (int) $results['database']['size_bytes'] : 0;
        $snapshot       = wp_json_encode( $this->compact_snapshot( $results ) );

        $inserted = $wpdb->insert(
            self::table_name(),
            array(
                'scanned_at'           => $results['scanned_at'] ?? current_time( 'mysql' ),
                'overall_score'        => (int) ( $health['score'] ?? 0 ),
                'performance_score'    => (int) ( $categories['performance'] ?? 0 ),
                'security_score'       => (int) ( $categories['security'] ?? 0 ),
                'seo_score'            => (int) ( $categories['seo'] ?? 0 ),
                'accessibility_score'  => (int) ( $categories['accessibility'] ?? 0 ),
                'updates_score'        => (int) ( $categories['updates'] ?? 0 ),
                'critical_issues'      => $counts['critical'],
                'high_issues'          => $counts['high'],
                'medium_issues'        => $counts['medium'],
                'low_issues'           => $counts['low'],
                'issue_count'          => count( $issues ),
                'scan_duration'        => (float) ( $results['scan_duration'] ?? 0 ),
                'database_size_bytes'  => $database_bytes,
                'plugin_count'         => (int) ( $results['plugins']['active'] ?? 0 ),
                'plugin_version'       => SITEPILOT_AI_VERSION,
                'snapshot'             => $snapshot,
            ),
            array( '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%s', '%s' )
        );

        return false === $inserted ? 0 : (int) $wpdb->insert_id;
    }

    public function recent( int $limit = 30 ): array {
        global $wpdb;
        $limit = max( 1, min( 365, $limit ) );
        $sql   = $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' ORDER BY scanned_at DESC, id DESC LIMIT %d', $limit );
        return $wpdb->get_results( $sql, ARRAY_A ) ?: array();
    }

    public function latest(): ?array {
        $rows = $this->recent( 1 );
        return $rows[0] ?? null;
    }

    public function previous_to_latest(): ?array {
        $rows = $this->recent( 2 );
        return $rows[1] ?? null;
    }

    public function count(): int {
        global $wpdb;
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table_name() );
    }

    public function delete_all(): void {
        global $wpdb;
        $wpdb->query( 'TRUNCATE TABLE ' . self::table_name() );
    }

    private function compact_snapshot( array $results ): array {
        return array(
            'wordpress'   => $results['wordpress'] ?? array(),
            'server'      => $results['server'] ?? array(),
            'theme'       => $results['theme'] ?? array(),
            'plugins'     => $results['plugins'] ?? array(),
            'security'    => $results['security'] ?? array(),
            'performance' => $results['performance'] ?? array(),
            'seo'         => $results['seo'] ?? array(),
            'database'    => $results['database'] ?? array(),
            'health'      => $results['health'] ?? array(),
        );
    }
}
