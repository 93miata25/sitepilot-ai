<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ContentScanner implements ScannerInterface {
    public function scan(): array {
        $posts       = wp_count_posts( 'post' );
        $pages       = wp_count_posts( 'page' );
        $attachments = wp_count_attachments();
        $media_total = 0;

        foreach ( (array) $attachments as $count ) {
            $media_total += (int) $count;
        }

        return array(
            'published_posts' => isset( $posts->publish ) ? (int) $posts->publish : 0,
            'published_pages' => isset( $pages->publish ) ? (int) $pages->publish : 0,
            'media_items'     => $media_total,
            'comments'        => (int) wp_count_comments()->approved,
        );
    }
}
