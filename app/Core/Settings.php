<?php
namespace SitePilot\Core;
if(!defined('ABSPATH')) exit;

/**
 * Central settings/options accessor for SitePilot AI.
 * Wraps a single wp_options row (array) so we don't sprawl autoloaded options.
 */
class Settings{
    const OPTION_KEY = 'sitepilot_ai_settings';

    protected static $defaults = [
        'ai_provider'        => 'anthropic', // anthropic|openai|none
        'ai_api_key'         => '',
        'ai_model'           => 'claude-sonnet-4-6',
        'scan_frequency'     => 'manual', // manual|daily|weekly
        'image_quality'      => 82,
        'security_alerts'    => true,
        'last_scan_id'       => null,
    ];

    protected static $cache = null;

    public static function all(){
        if(self::$cache === null){
            $stored = get_option(self::OPTION_KEY, []);
            if(!is_array($stored)) $stored = [];
            self::$cache = array_merge(self::$defaults, $stored);
        }
        return self::$cache;
    }

    public static function get($key, $default = null){
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set($key, $value){
        $all = self::all();
        $all[$key] = $value;
        self::$cache = $all;
        return update_option(self::OPTION_KEY, $all);
    }

    public static function update(array $values){
        $all = array_merge(self::all(), $values);
        self::$cache = $all;
        return update_option(self::OPTION_KEY, $all);
    }

    public static function reset(){
        self::$cache = self::$defaults;
        return update_option(self::OPTION_KEY, self::$defaults);
    }
}
