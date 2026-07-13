<?php
/**
 * Plugin Name: SitePilot AI
 * Plugin URI:  https://github.com/93miata25/sitepilot-ai
 * Description: A modular WordPress website scanner and optimization assistant.
 * Version:     0.8.0
 * Author:      SitePilot AI
 * Text Domain: sitepilot-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SITEPILOT_AI_VERSION', '0.8.0' );
define( 'SITEPILOT_AI_FILE', __FILE__ );
define( 'SITEPILOT_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'SITEPILOT_AI_URL', plugin_dir_url( __FILE__ ) );

require_once SITEPILOT_AI_PATH . 'app/Core/Autoloader.php';

SitePilotAI\Core\Autoloader::register();

register_activation_hook( __FILE__, array( SitePilotAI\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( SitePilotAI\Core\Deactivator::class, 'deactivate' ) );

SitePilotAI\Core\Plugin::instance()->run();
