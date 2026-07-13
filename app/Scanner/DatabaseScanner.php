<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class DatabaseScanner implements ScannerInterface {
    public function scan(): array {
        global $wpdb;

        $tables = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $bytes  = 0;
        foreach ( (array) $tables as $table ) {
            $bytes += (int) ( $table['Data_length'] ?? 0 ) + (int) ( $table['Index_length'] ?? 0 );
        }

        $autoload_bytes = (int) $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto')"
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return array(
            'size_bytes'          => $bytes,
            'size'                => size_format( $bytes ),
            'autoload_bytes'      => $autoload_bytes,
            'autoload_size'       => size_format( $autoload_bytes ),
            'table_count'         => count( (array) $tables ),
            'database_version'    => $wpdb->db_version(),
        );
    }
}
