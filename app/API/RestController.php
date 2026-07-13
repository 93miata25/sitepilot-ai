<?php
namespace SitePilot\API;
use SitePilot\Reports\ReportBuilder;
use SitePilot\Optimizer\ImageOptimizer;
use SitePilot\Models\ScanRecord;
use SitePilot\Core\Settings;
if(!defined('ABSPATH')) exit;

/**
 * Registers wp-json/sitepilot-ai/v1/* routes used by assets/js/admin.js.
 * All routes require manage_options and a valid REST nonce.
 */
class RestController{

    const NAMESPACE_ = 'sitepilot-ai/v1';

    public function register(){
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(){
        register_rest_route(self::NAMESPACE_, '/scan', [
            'methods'             => 'POST',
            'callback'            => [$this, 'runScan'],
            'permission_callback' => [$this, 'permissions'],
            'args' => [
                'url' => ['type' => 'string', 'required' => false],
            ],
        ]);

        register_rest_route(self::NAMESPACE_, '/scans', [
            'methods'             => 'GET',
            'callback'            => [$this, 'listScans'],
            'permission_callback' => [$this, 'permissions'],
        ]);

        register_rest_route(self::NAMESPACE_, '/scans/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getScan'],
            'permission_callback' => [$this, 'permissions'],
        ]);

        register_rest_route(self::NAMESPACE_, '/images/audit', [
            'methods'             => 'GET',
            'callback'            => [$this, 'auditImages'],
            'permission_callback' => [$this, 'permissions'],
        ]);

        register_rest_route(self::NAMESPACE_, '/images/(?P<id>\d+)/optimize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'optimizeImage'],
            'permission_callback' => [$this, 'permissions'],
        ]);

        register_rest_route(self::NAMESPACE_, '/settings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getSettings'],
            'permission_callback' => [$this, 'permissions'],
        ]);

        register_rest_route(self::NAMESPACE_, '/settings', [
            'methods'             => 'POST',
            'callback'            => [$this, 'updateSettings'],
            'permission_callback' => [$this, 'permissions'],
        ]);
    }

    public function permissions(){
        return current_user_can('manage_options');
    }

    public function runScan(\WP_REST_Request $request){
        $url = $request->get_param('url') ?: null;
        $report = (new ReportBuilder())->runFullScan($url);
        return rest_ensure_response($report);
    }

    public function listScans(\WP_REST_Request $request){
        return rest_ensure_response(ScanRecord::all(50));
    }

    public function getScan(\WP_REST_Request $request){
        $record = ScanRecord::find((int)$request->get_param('id'));
        if(!$record){
            return new \WP_Error('not_found', 'Scan not found', ['status' => 404]);
        }
        return rest_ensure_response($record);
    }

    public function auditImages(\WP_REST_Request $request){
        return rest_ensure_response((new ImageOptimizer())->audit());
    }

    public function optimizeImage(\WP_REST_Request $request){
        $id = (int)$request->get_param('id');
        $result = (new ImageOptimizer())->optimize($id);
        if(!$result['ok']){
            return new \WP_Error('optimize_failed', $result['error'], ['status' => 400]);
        }
        return rest_ensure_response($result);
    }

    public function getSettings(){
        $settings = Settings::all();
        // Never expose the raw API key to the browser.
        if(!empty($settings['ai_api_key'])){
            $settings['ai_api_key'] = '••••••••'.substr($settings['ai_api_key'], -4);
            $settings['ai_api_key_set'] = true;
        } else {
            $settings['ai_api_key_set'] = false;
        }
        unset($settings['ai_api_key']);
        return rest_ensure_response($settings);
    }

    public function updateSettings(\WP_REST_Request $request){
        $allowed = ['ai_provider', 'ai_api_key', 'ai_model', 'scan_frequency', 'image_quality', 'security_alerts'];
        $body = $request->get_json_params() ?: [];
        $updates = [];

        foreach($allowed as $key){
            if(array_key_exists($key, $body) && $body[$key] !== ''){
                $updates[$key] = $body[$key];
            }
        }

        Settings::update($updates);
        return rest_ensure_response(['ok' => true]);
    }
}
