<?php
namespace SitePilotAI\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SeoScanner implements ScannerInterface {
    public function scan(): array {
        $robots_url  = home_url( '/robots.txt' );
        $sitemap_url = home_url( '/wp-sitemap.xml' );

        return array(
            'permalinks'       => '' !== (string) get_option( 'permalink_structure', '' ),
            'search_visible'   => '0' === (string) get_option( 'blog_public', '1' ) ? false : true,
            'site_title'       => trim( (string) get_option( 'blogname', '' ) ),
            'tagline'          => trim( (string) get_option( 'blogdescription', '' ) ),
            'robots_url'       => $robots_url,
            'robots_available' => $this->url_is_available( $robots_url ),
            'sitemap_url'      => $sitemap_url,
            'sitemap_available'=> $this->url_is_available( $sitemap_url ),
        );
    }

    private function url_is_available( string $url ): bool {
        $response = wp_remote_head( $url, array( 'timeout' => 6, 'redirection' => 3 ) );
        if ( is_wp_error( $response ) ) {
            return false;
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        return $code >= 200 && $code < 400;
    }
}
