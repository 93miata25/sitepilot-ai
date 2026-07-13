<?php
namespace SitePilotAI\Modules\Database;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class DatabaseScanner {
    public function scan(): array {
        global $wpdb;
        $revisions = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
        $trashed_posts = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
        $spam_comments = (int) $wpdb->get_var( "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" );
        $trashed_comments = (int) $wpdb->get_var( "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" );
        $expired_transients = $this->count_expired_transients();
        $size_before = $this->database_size();
        return array(
            'revisions' => $revisions,
            'trashed_posts' => $trashed_posts,
            'spam_comments' => $spam_comments,
            'trashed_comments' => $trashed_comments,
            'expired_transients' => $expired_transients,
            'database_bytes' => $size_before,
            'database_size' => size_format( $size_before, 2 ),
            'total_cleanup' => $revisions + $trashed_posts + $spam_comments + $trashed_comments + $expired_transients,
        );
    }

    public function database_size(): int {
        global $wpdb;
        $bytes = $wpdb->get_var( $wpdb->prepare(
            'SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = %s',
            DB_NAME
        ) );
        return max( 0, (int) $bytes );
    }

    private function count_expired_transients(): int {
        global $wpdb;
        $like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
        $now = time();
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(option_id) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $like,
            $now
        ) );
    }
}
