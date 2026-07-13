<?php
namespace SitePilot\Admin;
if(!defined('ABSPATH')) exit;

class Admin{
    public function __construct(){
        add_action('admin_menu',[$this,'menu']);
    }

    public function menu(){
        add_menu_page(
            'SitePilot AI',
            'SitePilot AI',
            'manage_options',
            'sitepilot-ai',
            [$this,'dashboard'],
            'dashicons-performance'
        );
    }

    public function dashboard(){
        ?>
        <div class="wrap">
            <h1>SitePilot AI</h1>
            <h2>Version 0.1.0</h2>
            <p><strong>Status:</strong> Project Skeleton Installed</p>
            <hr>
            <h3>Coming Next</h3>
            <ul>
                <li>Website Scanner</li>
                <li>Performance Score</li>
                <li>SEO Analyzer</li>
                <li>Image Optimizer</li>
                <li>AI Assistant</li>
            </ul>
            <p><button class="button button-primary">Scan Website (Coming Soon)</button></p>
        </div>
        <?php
    }
}
