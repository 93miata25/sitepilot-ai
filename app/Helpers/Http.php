<?php
namespace SitePilot\Helpers;
if(!defined('ABSPATH')) exit;

/**
 * Thin wrapper around wp_remote_get/post so scanner/SEO/AI modules
 * don't each reinvent error handling.
 */
class Http{

    public static function get($url, $args = []){
        $defaults = [
            'timeout'     => 15,
            'redirection' => 5,
            'user-agent'  => 'SitePilotAI/'.(defined('SITEPILOT_VERSION') ? SITEPILOT_VERSION : '0.1.0').' (+wordpress-plugin)',
        ];
        $response = wp_remote_get($url, array_merge($defaults, $args));
        return self::normalize($response);
    }

    public static function post($url, $body = [], $args = []){
        $defaults = [
            'timeout' => 20,
            'body'    => $body,
        ];
        $response = wp_remote_post($url, array_merge($defaults, $args));
        return self::normalize($response);
    }

    protected static function normalize($response){
        if(is_wp_error($response)){
            return [
                'ok'    => false,
                'error' => $response->get_error_message(),
                'code'  => 0,
                'body'  => '',
                'headers' => [],
            ];
        }
        $code = wp_remote_retrieve_response_code($response);
        return [
            'ok'      => $code >= 200 && $code < 400,
            'error'   => null,
            'code'    => $code,
            'body'    => wp_remote_retrieve_body($response),
            'headers' => wp_remote_retrieve_headers($response),
        ];
    }
}
