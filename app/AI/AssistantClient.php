<?php
namespace SitePilot\AI;
use SitePilot\Core\Settings;
use SitePilot\Helpers\Http;
use SitePilot\Helpers\Logger;
if(!defined('ABSPATH')) exit;

/**
 * Talks to the configured AI provider (Anthropic or OpenAI) to turn
 * raw scan results into a plain-English action plan. The API key is
 * stored via Settings (wp_options) and never leaves the server except
 * to call the provider's API directly.
 */
class AssistantClient{

    protected $provider;
    protected $apiKey;
    protected $model;

    public function __construct(){
        $this->provider = Settings::get('ai_provider', 'anthropic');
        $this->apiKey   = Settings::get('ai_api_key', '');
        $this->model    = Settings::get('ai_model', 'claude-sonnet-4-6');
    }

    public function isConfigured(){
        return $this->provider !== 'none' && !empty($this->apiKey);
    }

    /**
     * Summarize a combined scan result (performance + seo + security)
     * into a prioritized, plain-English action list.
     */
    public function summarizeScan(array $scanResults){
        if(!$this->isConfigured()){
            return ['ok' => false, 'error' => 'AI provider is not configured. Add an API key in SitePilot AI settings.'];
        }

        $prompt = $this->buildPrompt($scanResults);

        switch($this->provider){
            case 'anthropic':
                return $this->callAnthropic($prompt);
            case 'openai':
                return $this->callOpenAi($prompt);
            default:
                return ['ok' => false, 'error' => 'Unknown AI provider: '.$this->provider];
        }
    }

    protected function buildPrompt(array $scanResults){
        $summary = wp_json_encode($scanResults);
        return "You are a website optimization assistant. Given this JSON of scan results ".
               "(performance, SEO, and security checks) for a WordPress site, produce a short, ".
               "prioritized action plan for a non-technical site owner. Use plain language, group ".
               "by priority (High/Medium/Low), and keep it under 300 words.\n\nScan results:\n{$summary}";
    }

    protected function callAnthropic($prompt){
        $response = Http::post('https://api.anthropic.com/v1/messages', wp_json_encode([
            'model'      => $this->model,
            'max_tokens' => 800,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]), [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ],
        ]);

        if(!$response['ok']){
            Logger::error('Anthropic API call failed', ['code' => $response['code'], 'error' => $response['error']]);
            return ['ok' => false, 'error' => $response['error'] ?: 'Anthropic API returned HTTP '.$response['code']];
        }

        $data = json_decode($response['body'], true);
        $text = $data['content'][0]['text'] ?? null;

        if(!$text){
            return ['ok' => false, 'error' => 'Unexpected response format from Anthropic API'];
        }

        return ['ok' => true, 'summary' => $text];
    }

    protected function callOpenAi($prompt){
        $response = Http::post('https://api.openai.com/v1/chat/completions', wp_json_encode([
            'model'    => $this->model ?: 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 800,
        ]), [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ],
        ]);

        if(!$response['ok']){
            Logger::error('OpenAI API call failed', ['code' => $response['code'], 'error' => $response['error']]);
            return ['ok' => false, 'error' => $response['error'] ?: 'OpenAI API returned HTTP '.$response['code']];
        }

        $data = json_decode($response['body'], true);
        $text = $data['choices'][0]['message']['content'] ?? null;

        if(!$text){
            return ['ok' => false, 'error' => 'Unexpected response format from OpenAI API'];
        }

        return ['ok' => true, 'summary' => $text];
    }
}
