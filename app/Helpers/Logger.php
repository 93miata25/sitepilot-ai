<?php
namespace SitePilot\Helpers;
if(!defined('ABSPATH')) exit;

class Logger{
    const LEVELS = ['debug','info','warning','error'];

    public static function log($message, $level = 'info', array $context = []){
        if(!in_array($level, self::LEVELS, true)) $level = 'info';

        $line = sprintf(
            "[%s] %s: %s%s\n",
            gmdate('Y-m-d H:i:s'),
            strtoupper($level),
            is_string($message) ? $message : wp_json_encode($message),
            $context ? ' '.wp_json_encode($context) : ''
        );

        $file = SITEPILOT_PATH.'storage/logs/sitepilot-'.gmdate('Y-m').'.log';
        // Best-effort write; never let logging break the caller.
        // phpcs:ignore WordPress.WP.AlternativeFunctions
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function debug($message, array $context = []){ self::log($message, 'debug', $context); }
    public static function info($message, array $context = []){ self::log($message, 'info', $context); }
    public static function warning($message, array $context = []){ self::log($message, 'warning', $context); }
    public static function error($message, array $context = []){ self::log($message, 'error', $context); }
}
