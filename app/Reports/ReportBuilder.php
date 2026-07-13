<?php
namespace SitePilot\Reports;
use SitePilot\Scanner\WebsiteScanner;
use SitePilot\SEO\SeoAnalyzer;
use SitePilot\Security\SecurityScanner;
use SitePilot\AI\AssistantClient;
if(!defined('ABSPATH')) exit;

/**
 * Orchestrates a full scan: performance + SEO + security, and
 * optionally asks the AI assistant to summarize it. This is the
 * single entry point the admin UI / REST API call for "Scan Website".
 */
class ReportBuilder{

    public function runFullScan($url = null, $includeAi = true){
        $performance = (new WebsiteScanner($url))->run();
        $seo         = (new SeoAnalyzer($url))->run();
        $security    = (new SecurityScanner())->run();

        $overall = $this->overallScore($performance, $seo, $security);

        $report = [
            'generated_at' => current_time('mysql'),
            'overall_score'=> $overall,
            'performance'  => $performance,
            'seo'          => $seo,
            'security'     => $security,
        ];

        if($includeAi){
            $assistant = new AssistantClient();
            if($assistant->isConfigured()){
                $ai = $assistant->summarizeScan($report);
                $report['ai_summary'] = $ai['ok'] ? $ai['summary'] : null;
                $report['ai_error']   = $ai['ok'] ? null : $ai['error'];
            } else {
                $report['ai_summary'] = null;
                $report['ai_error']   = 'not_configured';
            }
        }

        return $report;
    }

    protected function overallScore($performance, $seo, $security){
        $scores = array_filter([
            $performance['score'] ?? null,
            $seo['score'] ?? null,
            $security['score'] ?? null,
        ], fn($s) => $s !== null);

        return $scores ? (int)round(array_sum($scores) / count($scores)) : 0;
    }
}
