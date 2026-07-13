<?php
namespace SitePilot\Models;
if(!defined('ABSPATH')) exit;

/**
 * CRUD for the {$wpdb->prefix}sitepilot_scans table.
 */
class ScanRecord{

    protected static function table(){
        global $wpdb;
        return $wpdb->prefix.'sitepilot_scans';
    }

    public static function create($scanType, $url, $score, array $results){
        global $wpdb;
        $wpdb->insert(self::table(), [
            'scan_type'  => sanitize_key($scanType),
            'url'        => esc_url_raw($url),
            'score'      => max(0, min(100, (int)$score)),
            'results'    => wp_json_encode($results),
            'created_at' => current_time('mysql'),
        ], ['%s','%s','%d','%s','%s']);

        return (int)$wpdb->insert_id;
    }

    public static function find($id){
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM ".self::table()." WHERE id = %d", $id
        ), ARRAY_A);
        return self::hydrate($row);
    }

    public static function latest($scanType = null, $limit = 1){
        global $wpdb;
        $table = self::table();
        if($scanType){
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE scan_type = %s ORDER BY created_at DESC LIMIT %d",
                $scanType, $limit
            ), ARRAY_A);
        } else {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit
            ), ARRAY_A);
        }
        return array_map([self::class, 'hydrate'], $rows ?: []);
    }

    public static function all($limit = 50, $offset = 0){
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM ".self::table()." ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);
        return array_map([self::class, 'hydrate'], $rows ?: []);
    }

    public static function delete($id){
        global $wpdb;
        return (bool)$wpdb->delete(self::table(), ['id' => (int)$id], ['%d']);
    }

    protected static function hydrate($row){
        if(!$row) return null;
        $row['results'] = json_decode($row['results'], true) ?: [];
        return $row;
    }
}
