<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PerformanceScanner implements ScannerInterface {
    public function scan(): array {
        $response = wp_remote_get(
            home_url( '/' ),
            array(
                'timeout'     => 8,
                'redirection' => 3,
                'user-agent'  => 'SitePilot AI/' . SITEPILOT_AI_VERSION . '; ' . home_url( '/' ),
            )
        );

        $headers = array();
        $status  = 0;

        if ( ! is_wp_error( $response ) ) {
            $status = (int) wp_remote_retrieve_response_code( $response );
            foreach ( wp_remote_retrieve_headers( $response ) as $name => $value ) {
                $headers[ strtolower( (string) $name ) ] = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
            }
        }

        $encoding      = strtolower( $headers['content-encoding'] ?? '' );
        $cache_control = strtolower( $headers['cache-control'] ?? '' );
        $vary          = strtolower( $headers['vary'] ?? '' );

        return array(
            'homepage_status'    => $status,
            'compression'        => false !== strpos( $encoding, 'br' ) ? 'Brotli' : ( false !== strpos( $encoding, 'gzip' ) ? 'GZIP' : 'Not detected' ),
            'browser_cache'      => '' !== $cache_control && false === strpos( $cache_control, 'no-store' ),
            'cache_control'      => $headers['cache-control'] ?? '',
            'content_encoding'   => $headers['content-encoding'] ?? '',
            'vary_accept'        => false !== strpos( $vary, 'accept-encoding' ),
            'object_cache'       => wp_using_ext_object_cache(),
            'page_cache_hint'    => $this->detect_page_cache( $headers ),
            'opcache'            => function_exists( 'opcache_get_status' ) && false !== @opcache_get_status( false ),
            'redis_extension'    => extension_loaded( 'redis' ),
            'memcached_extension'=> extension_loaded( 'memcached' ),
        );
    }

    private function detect_page_cache( array $headers ): string {
        $signals = array(
            'cf-cache-status'       => 'Cloudflare',
            'x-cache'               => 'Proxy/Page cache',
            'x-litespeed-cache'     => 'LiteSpeed Cache',
            'x-wp-cf-super-cache'   => 'WP Cloudflare Super Page Cache',
            'x-kinsta-cache'        => 'Kinsta Cache',
            'x-fastcgi-cache'       => 'FastCGI Cache',
            'x-proxy-cache'         => 'Proxy Cache',
        );

        foreach ( $signals as $header => $label ) {
            if ( ! empty( $headers[ $header ] ) ) {
                return $label;
            }
        }

        return __( 'Not detected', 'sitepilot-ai' );
    }
}
