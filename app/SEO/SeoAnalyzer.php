<?php
namespace SitePilot\SEO;
use SitePilot\Helpers\Http;
use SitePilot\Models\ScanRecord;
if(!defined('ABSPATH')) exit;

/**
 * Parses on-page HTML for common SEO signals. Uses DOMDocument
 * rather than regex where structure matters (headings, links).
 */
class SeoAnalyzer{

    protected $url;
    protected $dom;
    protected $xpath;
    protected $issues = [];
    protected $passes = [];

    public function __construct($url = null){
        $this->url = $url ?: home_url('/');
    }

    public function run(){
        $response = Http::get($this->url);
        if(!$response['ok']){
            return ['id' => null, 'score' => 0, 'error' => $response['error'] ?: 'Fetch failed'];
        }

        $this->loadDom($response['body']);

        $this->checkTitle();
        $this->checkMetaDescription();
        $this->checkH1();
        $this->checkHeadingOrder();
        $this->checkImageAlts();
        $this->checkCanonical();
        $this->checkRobotsMeta();
        $this->checkOpenGraph();
        $this->checkInternalLinks();
        $this->checkWordCount();

        $score = $this->computeScore();

        $results = [
            'url'    => $this->url,
            'issues' => $this->issues,
            'passes' => $this->passes,
        ];

        $id = ScanRecord::create('seo', $this->url, $score, $results);

        return array_merge(['id' => $id, 'score' => $score], $results);
    }

    protected function loadDom($html){
        $this->dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $this->dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        $this->xpath = new \DOMXPath($this->dom);
    }

    protected function record($key, $label, $pass, $detail, $weight){
        $entry = ['key' => $key, 'label' => $label, 'detail' => $detail, 'weight' => $weight];
        if($pass) $this->passes[] = $entry; else $this->issues[] = $entry;
    }

    protected function tagText($tag){
        $nodes = $this->dom->getElementsByTagName($tag);
        return $nodes->length ? trim($nodes->item(0)->textContent) : '';
    }

    protected function metaContent($name, $attr = 'name'){
        $nodes = $this->xpath->query("//meta[@{$attr}='{$name}']");
        return $nodes->length ? trim($nodes->item(0)->getAttribute('content')) : '';
    }

    protected function checkTitle(){
        $title = $this->tagText('title');
        $len = strlen($title);
        $pass = $len >= 10 && $len <= 60;
        $this->record('title', 'Title tag present and 10-60 characters', $pass,
            $title ? "\"{$title}\" ({$len} chars)" : 'Missing <title>', 15);
    }

    protected function checkMetaDescription(){
        $desc = $this->metaContent('description');
        $len = strlen($desc);
        $pass = $len >= 50 && $len <= 160;
        $this->record('meta_description', 'Meta description present and 50-160 characters', $pass,
            $desc ? "{$len} chars" : 'Missing meta description', 15);
    }

    protected function checkH1(){
        $h1s = $this->dom->getElementsByTagName('h1');
        $pass = $h1s->length === 1;
        $this->record('h1', 'Exactly one H1 on the page', $pass,
            "{$h1s->length} H1 tag(s) found", 10);
    }

    protected function checkHeadingOrder(){
        $levels = [];
        foreach(['h1','h2','h3','h4','h5','h6'] as $i => $tag){
            $nodes = $this->dom->getElementsByTagName($tag);
            if($nodes->length) $levels[] = $i + 1;
        }
        $skipped = false;
        for($i = 1; $i < count($levels); $i++){
            if($levels[$i] - $levels[$i-1] > 1) $skipped = true;
        }
        $this->record('heading_order', 'Heading levels do not skip (e.g. H2 -> H4)', !$skipped,
            $skipped ? 'A heading level was skipped' : 'Heading order looks sequential', 5);
    }

    protected function checkImageAlts(){
        $imgs = $this->dom->getElementsByTagName('img');
        $total = $imgs->length;
        $missing = 0;
        foreach($imgs as $img){
            $alt = $img->getAttribute('alt');
            if(trim($alt) === '') $missing++;
        }
        $pass = $total === 0 || $missing === 0;
        $this->record('image_alt', 'All images have alt text', $pass,
            $total ? "{$missing} of {$total} images missing alt text" : 'No images found', 10);
    }

    protected function checkCanonical(){
        $nodes = $this->xpath->query("//link[@rel='canonical']");
        $pass = $nodes->length > 0;
        $this->record('canonical', 'Canonical link tag present', $pass,
            $pass ? $nodes->item(0)->getAttribute('href') : 'Missing <link rel="canonical">', 10);
    }

    protected function checkRobotsMeta(){
        $robots = $this->metaContent('robots');
        $blocked = stripos($robots, 'noindex') !== false;
        $this->record('robots', 'Page is not set to noindex', !$blocked,
            $robots ?: 'No robots meta tag (defaults to indexable)', 15);
    }

    protected function checkOpenGraph(){
        $title = $this->metaContent('og:title', 'property');
        $image = $this->metaContent('og:image', 'property');
        $pass = $title !== '' && $image !== '';
        $this->record('open_graph', 'Open Graph title + image present (social sharing)', $pass,
            $pass ? 'og:title and og:image found' : 'Missing og:title and/or og:image', 5);
    }

    protected function checkInternalLinks(){
        $links = $this->dom->getElementsByTagName('a');
        $host = wp_parse_url($this->url, PHP_URL_HOST);
        $internal = 0;
        foreach($links as $a){
            $href = $a->getAttribute('href');
            if($href === '' || $href[0] === '#') continue;
            $linkHost = wp_parse_url($href, PHP_URL_HOST);
            if(!$linkHost || $linkHost === $host) $internal++;
        }
        $pass = $internal >= 2;
        $this->record('internal_links', 'Page has at least 2 internal links', $pass,
            "{$internal} internal links found", 5);
    }

    protected function checkWordCount(){
        $body = $this->dom->getElementsByTagName('body');
        $text = $body->length ? $body->item(0)->textContent : '';
        $count = str_word_count(preg_replace('/\s+/', ' ', $text));
        $pass = $count >= 300;
        $this->record('word_count', 'Page has at least 300 words of content', $pass,
            "{$count} words", 10);
    }

    protected function computeScore(){
        $totalWeight = 0;
        $earned = 0;
        foreach(array_merge($this->passes, $this->issues) as $entry){
            $totalWeight += $entry['weight'];
        }
        foreach($this->passes as $entry){
            $earned += $entry['weight'];
        }
        return $totalWeight ? (int)round(($earned / $totalWeight) * 100) : 0;
    }
}
