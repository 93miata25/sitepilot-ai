<?php
namespace SitePilotAI\Issues;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Issue {
    private array $data;

    public function __construct( array $data ) {
        $defaults = array(
            'id'          => '',
            'category'    => 'general',
            'severity'    => 'low',
            'title'       => '',
            'description' => '',
            'fix'         => '',
            'impact'      => 0,
            'fixable'     => false,
            'fix_action'  => '',
        );

        $data = wp_parse_args( $data, $defaults );

        $this->data = array(
            'id'          => sanitize_key( (string) $data['id'] ),
            'category'    => sanitize_key( (string) $data['category'] ),
            'severity'    => sanitize_key( (string) $data['severity'] ),
            'title'       => (string) $data['title'],
            'description' => (string) $data['description'],
            'reason'      => (string) $data['description'],
            'fix'         => (string) $data['fix'],
            'impact'      => max( 0, (int) $data['impact'] ),
            'fixable'     => (bool) $data['fixable'],
            'fix_action'  => sanitize_key( (string) $data['fix_action'] ),
        );
    }

    public function to_array(): array {
        return $this->data;
    }
}
