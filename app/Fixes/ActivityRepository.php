<?php
namespace SitePilotAI\Fixes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ActivityRepository {
    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'sitepilot_ai_activity';
    }

    public static function create_table(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action_id varchar(100) NOT NULL,
            title varchar(190) NOT NULL,
            status varchar(30) NOT NULL,
            score_before smallint(5) unsigned NOT NULL DEFAULT 0,
            score_after smallint(5) unsigned NOT NULL DEFAULT 0,
            details text NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY action_id (action_id)
        ) {$charset};";

        dbDelta( $sql );
    }

    public function add( array $data ): void {
        global $wpdb;
        $wpdb->insert(
            self::table_name(),
            array(
                'created_at'   => current_time( 'mysql' ),
                'user_id'      => get_current_user_id(),
                'action_id'    => sanitize_key( (string) ( $data['action_id'] ?? '' ) ),
                'title'        => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
                'status'       => sanitize_key( (string) ( $data['status'] ?? 'success' ) ),
                'score_before' => max( 0, min( 100, (int) ( $data['score_before'] ?? 0 ) ) ),
                'score_after'  => max( 0, min( 100, (int) ( $data['score_after'] ?? 0 ) ) ),
                'details'      => sanitize_textarea_field( (string) ( $data['details'] ?? '' ) ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
        );
    }

    public function recent( int $limit = 10 ): array {
        global $wpdb;
        $limit = max( 1, min( 100, $limit ) );
        return $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' ORDER BY id DESC LIMIT %d', $limit ),
            ARRAY_A
        ) ?: array();
    }
}
