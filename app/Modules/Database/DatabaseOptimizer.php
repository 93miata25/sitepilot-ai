<?php
namespace SitePilotAI\Modules\Database;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class DatabaseOptimizer {
    public function run( array $tasks ): array {
        $allowed = array( 'expired_transients', 'spam_comments', 'trashed_comments', 'trashed_posts', 'revisions', 'optimize_tables' );
        $tasks = array_values( array_intersect( array_map( 'sanitize_key', $tasks ), $allowed ) );
        $scanner = new DatabaseScanner();
        $before = $scanner->database_size();
        $deleted = array();
        $errors = array();
        foreach ( $tasks as $task ) {
            switch ( $task ) {
                case 'expired_transients': $deleted[$task] = $this->delete_expired_transients(); break;
                case 'spam_comments': $deleted[$task] = $this->delete_comments( 'spam' ); break;
                case 'trashed_comments': $deleted[$task] = $this->delete_comments( 'trash' ); break;
                case 'trashed_posts': $deleted[$task] = $this->delete_posts( "post_status = 'trash'" ); break;
                case 'revisions': $deleted[$task] = $this->delete_posts( "post_type = 'revision'" ); break;
                case 'optimize_tables':
                    $result = $this->optimize_tables();
                    $deleted[$task] = $result['optimized'];
                    $errors = array_merge( $errors, $result['errors'] );
                    break;
            }
        }
        wp_cache_flush();
        $after = $scanner->database_size();
        return array(
            'success' => empty( $errors ),
            'deleted' => $deleted,
            'errors' => $errors,
            'before_size' => size_format( $before, 2 ),
            'after_size' => size_format( $after, 2 ),
            'reclaimed_size' => size_format( max( 0, $before - $after ), 2 ),
        );
    }

    private function delete_expired_transients(): int {
        global $wpdb;
        $timeouts = $wpdb->get_col( $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like( '_transient_timeout_' ) . '%', time()
        ) );
        $deleted = 0;
        foreach ( $timeouts as $timeout ) {
            $name = substr( $timeout, strlen( '_transient_timeout_' ) );
            if ( delete_transient( $name ) ) { $deleted++; }
        }
        return $deleted;
    }

    private function delete_comments( string $status ): int {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = %s", $status ) );
        $deleted = 0;
        foreach ( $ids as $id ) { if ( wp_delete_comment( (int) $id, true ) ) { $deleted++; } }
        return $deleted;
    }

    private function delete_posts( string $where ): int {
        global $wpdb;
        $ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE {$where}" );
        $deleted = 0;
        foreach ( $ids as $id ) { if ( wp_delete_post( (int) $id, true ) ) { $deleted++; } }
        return $deleted;
    }

    private function optimize_tables(): array {
        global $wpdb;
        $tables = $wpdb->get_col( 'SHOW TABLES' );
        $optimized = 0;
        $errors = array();
        foreach ( $tables as $table ) {
            if ( 0 !== strpos( $table, $wpdb->prefix ) ) { continue; }
            $safe = str_replace( '`', '``', $table );
            $result = $wpdb->query( "OPTIMIZE TABLE `{$safe}`" );
            if ( false === $result ) { $errors[] = $table; } else { $optimized++; }
        }
        return array( 'optimized' => $optimized, 'errors' => $errors );
    }
}
