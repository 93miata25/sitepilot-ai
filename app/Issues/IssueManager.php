<?php
namespace SitePilotAI\Issues;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class IssueManager {
    private const SEVERITY_ORDER = array(
        'critical' => 4,
        'high'     => 3,
        'medium'   => 2,
        'low'      => 1,
    );

    public function prepare( array $issues ): array {
        $prepared = array();

        foreach ( $issues as $issue ) {
            if ( ! is_array( $issue ) ) {
                continue;
            }

            $prepared[] = ( new Issue( $issue ) )->to_array();
        }

        usort(
            $prepared,
            static function ( array $left, array $right ): int {
                $left_weight  = self::SEVERITY_ORDER[ $left['severity'] ] ?? 0;
                $right_weight = self::SEVERITY_ORDER[ $right['severity'] ] ?? 0;

                if ( $left_weight === $right_weight ) {
                    return $right['impact'] <=> $left['impact'];
                }

                return $right_weight <=> $left_weight;
            }
        );

        return $prepared;
    }

    public function filter( array $issues, string $severity = '', string $category = '' ): array {
        $severity = sanitize_key( $severity );
        $category = sanitize_key( $category );

        return array_values(
            array_filter(
                $issues,
                static function ( array $issue ) use ( $severity, $category ): bool {
                    if ( $severity && $severity !== $issue['severity'] ) {
                        return false;
                    }

                    if ( $category && $category !== $issue['category'] ) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    public function counts( array $issues ): array {
        $counts = array(
            'all'      => count( $issues ),
            'critical' => 0,
            'high'     => 0,
            'medium'   => 0,
            'low'      => 0,
        );

        foreach ( $issues as $issue ) {
            $severity = $issue['severity'] ?? '';
            if ( isset( $counts[ $severity ] ) ) {
                ++$counts[ $severity ];
            }
        }

        return $counts;
    }

    public function categories( array $issues ): array {
        $categories = array();

        foreach ( $issues as $issue ) {
            $category = sanitize_key( (string) ( $issue['category'] ?? '' ) );
            if ( $category ) {
                $categories[ $category ] = ucwords( str_replace( '_', ' ', $category ) );
            }
        }

        asort( $categories );
        return $categories;
    }
}
