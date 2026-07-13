<?php
/*
Plugin Name: SitePilot AI
Description: AI Website Optimizer
Version: 0.1.0
Author: SitePilot AI
*/
if(!defined('ABSPATH')) exit;

define('SITEPILOT_PATH', plugin_dir_path(__FILE__));
define('SITEPILOT_URL', plugin_dir_url(__FILE__));

require_once SITEPILOT_PATH.'app/Admin/Admin.php';

register_activation_hook(__FILE__, function(){});
register_deactivation_hook(__FILE__, function(){});

new SitePilot\Admin\Admin();
