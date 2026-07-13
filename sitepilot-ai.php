<?php
/**
 * Plugin Name: SitePilot AI
 * Description: Website health scanner and optimization assistant for WordPress.
 * Version: 0.2.0
 * Author: SitePilot AI
 * Text Domain: sitepilot-ai
 */

if (! defined('ABSPATH')) {
    exit;
}

define('SITEPILOT_AI_VERSION', '0.2.0');
define('SITEPILOT_AI_FILE', __FILE__);
define('SITEPILOT_AI_PATH', plugin_dir_path(__FILE__));
define('SITEPILOT_AI_URL', plugin_dir_url(__FILE__));

require_once SITEPILOT_AI_PATH . 'app/Core/Autoloader.php';

SitePilotAI\Core\Autoloader::register();

register_activation_hook(__FILE__, array(SitePilotAI\Core\Activator::class, 'activate'));
register_deactivation_hook(__FILE__, array(SitePilotAI\Core\Deactivator::class, 'deactivate'));

add_action('plugins_loaded', static function (): void {
    SitePilotAI\Core\Plugin::instance()->boot();
});
