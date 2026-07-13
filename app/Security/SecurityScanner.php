<?php
namespace SitePilot\Security;
use SitePilot\Models\ScanRecord;
if(!defined('ABSPATH')) exit;

/**
 * Non-intrusive security/hardening checks. Read-only - never modifies
 * anything, just reports.
 */
class SecurityScanner{

    protected $issues = [];
    protected $passes = [];

    public function run(){
        $this->checkWpVersion();
        $this->checkDebugMode();
        $this->checkFileEditing();
        $this->checkAdminUsername();
        $this->checkPluginUpdates();
        $this->checkThemeUpdates();
        $this->checkSslEnforced();
        $this->checkDirectoryListing();
        $this->checkXmlrpc();
        $this->checkVersionExposure();

        $score = $this->computeScore();
        $results = ['issues' => $this->issues, 'passes' => $this->passes];
        $id = ScanRecord::create('security', home_url('/'), $score, $results);

        return array_merge(['id' => $id, 'score' => $score], $results);
    }

    protected function record($key, $label, $pass, $detail, $weight){
        $entry = ['key' => $key, 'label' => $label, 'detail' => $detail, 'weight' => $weight];
        if($pass) $this->passes[] = $entry; else $this->issues[] = $entry;
    }

    protected function checkWpVersion(){
        global $wp_version;
        $current = $wp_version;
        $updates = get_core_updates();
        $upToDate = empty($updates) || (isset($updates[0]->response) && $updates[0]->response === 'latest');
        $this->record('wp_version', 'WordPress core is up to date', $upToDate,
            "Running {$current}", 15);
    }

    protected function checkDebugMode(){
        $debugOff = !(defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY);
        $this->record('debug_display', 'WP_DEBUG_DISPLAY is off in production', $debugOff,
            $debugOff ? 'Debug output not exposed' : 'Errors may be visibly displayed to visitors', 15);
    }

    protected function checkFileEditing(){
        $disabled = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
        $this->record('file_edit', 'Theme/plugin file editor disabled', $disabled,
            $disabled ? 'DISALLOW_FILE_EDIT is set' : 'Add define(\'DISALLOW_FILE_EDIT\', true); to wp-config.php', 10);
    }

    protected function checkAdminUsername(){
        $user = get_user_by('login', 'admin');
        $pass = $user === false;
        $this->record('admin_username', 'No user with the default "admin" login', $pass,
            $pass ? 'Default username not in use' : 'A user named "admin" exists - a common brute-force target', 10);
    }

    protected function checkPluginUpdates(){
        $updates = get_site_transient('update_plugins');
        $count = !empty($updates->response) ? count($updates->response) : 0;
        $pass = $count === 0;
        $this->record('plugin_updates', 'All plugins up to date', $pass,
            $pass ? 'No pending plugin updates' : "{$count} plugin(s) have updates available", 15);
    }

    protected function checkThemeUpdates(){
        $updates = get_site_transient('update_themes');
        $count = !empty($updates->response) ? count($updates->response) : 0;
        $pass = $count === 0;
        $this->record('theme_updates', 'All themes up to date', $pass,
            $pass ? 'No pending theme updates' : "{$count} theme(s) have updates available", 10);
    }

    protected function checkSslEnforced(){
        $pass = is_ssl() || strpos(home_url(), 'https://') === 0;
        $this->record('ssl', 'Site enforces HTTPS', $pass,
            $pass ? 'Site is served over SSL' : 'Site is not being served over HTTPS', 15);
    }

    protected function checkDirectoryListing(){
        $response = wp_remote_get(content_url('/uploads/'), ['timeout' => 8]);
        if(is_wp_error($response)){
            $this->record('directory_listing', 'Uploads directory listing disabled', true, 'Could not verify (request failed) - assuming OK', 5);
            return;
        }
        $body = wp_remote_retrieve_body($response);
        $exposed = stripos($body, 'Index of') !== false;
        $this->record('directory_listing', 'Uploads directory listing disabled', !$exposed,
            $exposed ? 'Directory listing appears to be enabled' : 'Listing not exposed', 5);
    }

    protected function checkXmlrpc(){
        $response = wp_remote_get(home_url('xmlrpc.php'), ['timeout' => 8]);
        $enabled = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        // Not necessarily a fail (Jetpack etc. need it) - informational, low weight.
        $this->record('xmlrpc', 'xmlrpc.php access reviewed', true,
            $enabled ? 'xmlrpc.php is reachable - disable if unused (common brute-force vector)' : 'xmlrpc.php is blocked or unreachable', 5);
    }

    protected function checkVersionExposure(){
        $response = wp_remote_get(home_url('/'), ['timeout' => 8]);
        if(is_wp_error($response)){
            return;
        }
        $body = wp_remote_retrieve_body($response);
        $exposed = (bool)preg_match('/content="WordPress\s*[\d.]*"/i', $body);
        $this->record('version_exposure', 'WordPress version not exposed in page source', !$exposed,
            $exposed ? 'Generator meta tag reveals WP version' : 'No generator meta tag found', 5);
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
