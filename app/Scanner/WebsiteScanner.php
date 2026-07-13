<?php
namespace SitePilot\Scanner;
use SitePilot\Helpers\Http;
use SitePilot\Helpers\Logger;
use SitePilot\Models\ScanRecord;
if(!defined('ABSPATH')) exit;

/**
 * Fetches the homepage (or a given URL) and runs a set of lightweight
 * performance/health checks. This is deliberately dependency-free
 * (no headless browser) so it works on any host.
 */
class WebsiteScanner{

    protected $url;
    protected $html = '';
    protected $loadTimeMs = 0;
    protected $pageSizeBytes = 0;
    protected $checks = [];

    public function __construct($url = null){
        $this->url = $url ?: home_url('/');
    }

    public function run(){
        $start = microtime(true);
        $response = Http::get($this->url);
        $this->loadTimeMs = (int)round((microtime(true) - $start) * 1000);

        if(!$response['ok']){
            Logger::error('Scanner fetch failed', ['url' => $this->url, 'error' => $response['error']]);
            return $this->fail($response['error'] ?: 'Could not fetch URL (HTTP '.$response['code'].')');
        }

        $this->html = $response['body'];
        $this->pageSizeBytes = strlen($this->html);

        $this->checkLoadTime();
        $this->checkPageSize();
        $this->checkGzip($response['headers']);
        $this->checkCaching($response['headers']);
        $this->checkHttps();
        $this->checkImagesMissingDimensions();
        $this->checkRenderBlockingAssets();
        $this->checkMobileViewport();

        $score = $this->computeScore();

        $results = [
            'url'            => $this->url,
            'load_time_ms'   => $this->loadTimeMs,
            'page_size_kb'   => round($this->pageSizeBytes / 1024, 1),
            'checks'         => $this->checks,
        ];

        $id = ScanRecord::create('performance', $this->url, $score, $results);
        \SitePilot\Core\Settings::set('last_scan_id', $id);

        return array_merge(['id' => $id, 'score' => $score], $results);
    }

    protected function fail($message){
        return ['id' => null, 'score' => 0, 'error' => $message, 'checks' => []];
    }

    protected function addCheck($key, $label, $pass, $detail = '', $weight = 10){
        $this->checks[$key] = [
            'label'  => $label,
            'pass'   => (bool)$pass,
            'detail' => $detail,
            'weight' => $weight,
        ];
    }

    protected function checkLoadTime(){
        $pass = $this->loadTimeMs < 1500;
        $this->addCheck('load_time', 'Page loads in under 1.5s', $pass,
            $this->loadTimeMs.'ms', 20);
    }

    protected function checkPageSize(){
        $kb = $this->pageSizeBytes / 1024;
        $pass = $kb < 500;
        $this->addCheck('page_size', 'HTML payload under 500KB', $pass,
            round($kb, 1).'KB', 10);
    }

    protected function checkGzip($headers){
        $encoding = isset($headers['content-encoding']) ? $headers['content-encoding'] : '';
        $pass = stripos($encoding, 'gzip') !== false || stripos($encoding, 'br') !== false;
        $this->addCheck('compression', 'Response is compressed (gzip/brotli)', $pass,
            $pass ? $encoding : 'No compression header found', 15);
    }

    protected function checkCaching($headers){
        $cacheControl = isset($headers['cache-control']) ? $headers['cache-control'] : '';
        $pass = (bool)$cacheControl && stripos($cacheControl, 'no-store') === false;
        $this->addCheck('caching', 'Cache-Control header present', $pass,
            $cacheControl ?: 'Missing Cache-Control header', 10);
    }

    protected function checkHttps(){
        $pass = strpos($this->url, 'https://') === 0;
        $this->addCheck('https', 'Site served over HTTPS', $pass, $this->url, 15);
    }

    protected function checkImagesMissingDimensions(){
        preg_match_all('/<img[^>]*>/i', $this->html, $matches);
        $total = count($matches[0]);
        $missing = 0;
        foreach($matches[0] as $tag){
            if(!preg_match('/\swidth\s*=/i', $tag) || !preg_match('/\sheight\s*=/i', $tag)){
                $missing++;
            }
        }
        $pass = $total === 0 || $missing === 0;
        $this->addCheck('image_dimensions', 'Images specify width/height (prevents layout shift)', $pass,
            $total ? "{$missing} of {$total} images missing dimensions" : 'No images found', 10);
    }

    protected function checkRenderBlockingAssets(){
        preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $this->html, $css);
        preg_match_all('/<script(?![^>]*(?:async|defer))[^>]*src=[^>]*>/i', $this->html, $js);
        $blockingCount = count($css[0]) + count($js[0]);
        $pass = $blockingCount <= 4;
        $this->addCheck('render_blocking', 'Few render-blocking CSS/JS tags in <head>', $pass,
            "{$blockingCount} blocking assets detected", 10);
    }

    protected function checkMobileViewport(){
        $pass = (bool)preg_match('/<meta[^>]+name=["\']viewport["\']/i', $this->html);
        $this->addCheck('viewport', 'Mobile viewport meta tag present', $pass,
            $pass ? 'Found' : 'Missing <meta name="viewport">', 10);
    }

    protected function computeScore(){
        $totalWeight = 0;
        $earned = 0;
        foreach($this->checks as $check){
            $totalWeight += $check['weight'];
            if($check['pass']) $earned += $check['weight'];
        }
        return $totalWeight ? (int)round(($earned / $totalWeight) * 100) : 0;
    }
}
