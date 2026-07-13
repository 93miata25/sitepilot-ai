<?php
namespace SitePilotAI\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PerformanceScanner {
    public function scan(): array {
        global $wpdb;

        $memory_limit_bytes = $this->to_bytes( (string) ini_get( 'memory_limit' ) );
        $autoload_bytes     = (int) $wpdb->get_var(
            "SELECT SUM(LENGTH(option_name) + LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto')"
        );
        $media = $this->media_summary();
        $compression = $this->compression_status();
        $page_cache = $this->detect_page_cache();
        $object_cache = wp_using_ext_object_cache();
        $opcache = function_exists( 'opcache_get_status' ) && false !== opcache_get_status( false );
        $cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

        $checks = array(
            'opcache' => array(
                'label'   => __( 'PHP OPcache', 'sitepilot-ai' ),
                'value'   => $opcache ? __( 'Enabled', 'sitepilot-ai' ) : __( 'Not detected', 'sitepilot-ai' ),
                'status'  => $opcache ? 'good' : 'warning',
                'impact'  => 10,
                'advice'  => __( 'Enable OPcache in PHP to avoid recompiling PHP files on every request.', 'sitepilot-ai' ),
            ),
            'object_cache' => array(
                'label'   => __( 'Persistent object cache', 'sitepilot-ai' ),
                'value'   => $object_cache ? __( 'Enabled', 'sitepilot-ai' ) : __( 'Not detected', 'sitepilot-ai' ),
                'status'  => $object_cache ? 'good' : 'warning',
                'impact'  => 12,
                'advice'  => __( 'Use Redis or Memcached for sites with dynamic traffic or heavy database usage.', 'sitepilot-ai' ),
            ),
            'page_cache' => array(
                'label'   => __( 'Page cache', 'sitepilot-ai' ),
                'value'   => $page_cache['label'],
                'status'  => $page_cache['enabled'] ? 'good' : 'warning',
                'impact'  => 18,
                'advice'  => __( 'Enable a full-page cache or server cache to reduce PHP and database work.', 'sitepilot-ai' ),
            ),
            'compression' => array(
                'label'   => __( 'HTTP compression', 'sitepilot-ai' ),
                'value'   => $compression['label'],
                'status'  => $compression['enabled'] ? 'good' : 'warning',
                'impact'  => 10,
                'advice'  => __( 'Enable Brotli or GZIP compression at the server or CDN layer.', 'sitepilot-ai' ),
            ),
            'memory' => array(
                'label'   => __( 'PHP memory limit', 'sitepilot-ai' ),
                'value'   => ini_get( 'memory_limit' ) ?: __( 'Unknown', 'sitepilot-ai' ),
                'status'  => $memory_limit_bytes >= 268435456 || -1 === $memory_limit_bytes ? 'good' : ( $memory_limit_bytes >= 134217728 ? 'warning' : 'bad' ),
                'impact'  => 8,
                'advice'  => __( 'Use at least 256 MB for sites with page builders, ecommerce, or many plugins.', 'sitepilot-ai' ),
            ),
            'autoload' => array(
                'label'   => __( 'Autoloaded options', 'sitepilot-ai' ),
                'value'   => size_format( $autoload_bytes, 2 ),
                'status'  => $autoload_bytes <= 800000 ? 'good' : ( $autoload_bytes <= 1500000 ? 'warning' : 'bad' ),
                'impact'  => 12,
                'advice'  => __( 'Keep autoloaded options below roughly 800 KB to reduce work on every request.', 'sitepilot-ai' ),
            ),
            'cron' => array(
                'label'   => __( 'WordPress cron', 'sitepilot-ai' ),
                'value'   => $cron_disabled ? __( 'Disabled in wp-config.php', 'sitepilot-ai' ) : __( 'Enabled', 'sitepilot-ai' ),
                'status'  => $cron_disabled ? 'warning' : 'good',
                'impact'  => 5,
                'advice'  => __( 'When WP-Cron is disabled, make sure a real server cron job calls wp-cron.php.', 'sitepilot-ai' ),
            ),
        );

        $score = 100;
        $recommendations = array();
        foreach ( $checks as $key => $check ) {
            if ( 'warning' === $check['status'] ) {
                $score -= (int) round( $check['impact'] * 0.55 );
            } elseif ( 'bad' === $check['status'] ) {
                $score -= (int) $check['impact'];
            }
            if ( 'good' !== $check['status'] ) {
                $recommendations[] = array(
                    'id'      => $key,
                    'title'   => $check['label'],
                    'message' => $check['advice'],
                    'impact'  => $check['impact'],
                    'status'  => $check['status'],
                );
            }
        }

        usort( $recommendations, static function ( array $a, array $b ): int {
            return $b['impact'] <=> $a['impact'];
        } );

        return array(
            'score'           => max( 0, min( 100, $score ) ),
            'checks'          => $checks,
            'recommendations' => $recommendations,
            'media'           => $media,
            'autoload_bytes'  => $autoload_bytes,
            'memory_limit'    => ini_get( 'memory_limit' ) ?: '',
            'max_execution'   => (int) ini_get( 'max_execution_time' ),
            'generated_at'    => current_time( 'mysql' ),
        );
    }

    private function detect_page_cache(): array {
        $plugins = array(
            'wp-rocket/wp-rocket.php'                       => 'WP Rocket',
            'litespeed-cache/litespeed-cache.php'           => 'LiteSpeed Cache',
            'w3-total-cache/w3-total-cache.php'              => 'W3 Total Cache',
            'wp-super-cache/wp-cache.php'                    => 'WP Super Cache',
            'flying-press/flying-press.php'                  => 'FlyingPress',
            'autoptimize/autoptimize.php'                    => 'Autoptimize',
            'sg-cachepress/sg-cachepress.php'                => 'SiteGround Optimizer',
            'breeze/breeze.php'                              => 'Breeze',
        );

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ( $plugins as $file => $label ) {
            if ( is_plugin_active( $file ) ) {
                return array( 'enabled' => true, 'label' => $label );
            }
        }

        if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
            return array( 'enabled' => true, 'label' => __( 'WP_CACHE enabled', 'sitepilot-ai' ) );
        }

        return array( 'enabled' => false, 'label' => __( 'Not detected', 'sitepilot-ai' ) );
    }

    private function compression_status(): array {
        $response = wp_remote_get(
            home_url( '/' ),
            array(
                'timeout'     => 8,
                'redirection' => 3,
                'headers'     => array( 'Accept-Encoding' => 'br, gzip, deflate' ),
                'user-agent'  => 'SitePilot-AI/' . SITEPILOT_AI_VERSION,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'enabled' => false, 'label' => __( 'Could not verify', 'sitepilot-ai' ) );
        }

        $encoding = strtolower( (string) wp_remote_retrieve_header( $response, 'content-encoding' ) );
        if ( false !== strpos( $encoding, 'br' ) ) {
            return array( 'enabled' => true, 'label' => 'Brotli' );
        }
        if ( false !== strpos( $encoding, 'gzip' ) ) {
            return array( 'enabled' => true, 'label' => 'GZIP' );
        }

        return array( 'enabled' => false, 'label' => __( 'Not detected', 'sitepilot-ai' ) );
    }

    private function media_summary(): array {
        global $wpdb;
        $count = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'" );
        $upload = wp_get_upload_dir();
        $size = 0;
        if ( empty( $upload['error'] ) && is_dir( $upload['basedir'] ) ) {
            $size = $this->directory_size( $upload['basedir'], 12000 );
        }
        return array(
            'count'      => $count,
            'size_bytes' => $size,
            'size'       => size_format( $size, 2 ),
        );
    }

    private function directory_size( string $path, int $limit ): int {
        $total = 0;
        $seen  = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ( $iterator as $file ) {
                if ( ++$seen > $limit ) {
                    break;
                }
                if ( $file->isFile() && $file->isReadable() ) {
                    $total += (int) $file->getSize();
                }
            }
        } catch ( \UnexpectedValueException $exception ) {
            return 0;
        }
        return $total;
    }

    private function to_bytes( string $value ): int {
        $value = trim( $value );
        if ( '-1' === $value ) {
            return -1;
        }
        $number = (float) $value;
        $unit   = strtolower( substr( $value, -1 ) );
        if ( 'g' === $unit ) {
            $number *= 1024;
        }
        if ( in_array( $unit, array( 'g', 'm' ), true ) ) {
            $number *= 1024;
        }
        if ( in_array( $unit, array( 'g', 'm', 'k' ), true ) ) {
            $number *= 1024;
        }
        return (int) $number;
    }
}
