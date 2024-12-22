<?php
/**
 * Plugin Name: Artisan Editor
 * Description: Custom blocks manager with PHP, template, JS, and CSS support
 * Version: 1.0.2
 * Author: Daniel Snell
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Check for ACF or Secure Custom Fields on plugin load
add_action('plugins_loaded', function () {
    if (!class_exists('ACF')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>Client Blocks requires ACF Pro or Secure Custom Fields to be installed and activated. Please install either <a href="https://www.advancedcustomfields.com/pro/" target="_blank">ACF Pro</a> or Secure Custom Fields to use this plugin.</p></div>';
        });
        return;
    }

    // Initialize Timber
    if (class_exists('Timber\Timber')) {
        Timber\Timber::init();
    }

    // Initialize plugin
    \ClientBlocks\Plugin::instance();
});

// Define plugin constants
define('CLIENT_BLOCKS_PATH', plugin_dir_path(__FILE__));
define('CLIENT_BLOCKS_URL', plugin_dir_url(__FILE__));
