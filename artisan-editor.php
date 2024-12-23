<?php
/*
Plugin Name: Client Blocks
Description: Custom blocks manager with PHP, template, JS, and CSS support
Version: 1.0.0
Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLIENT_BLOCKS_PATH', plugin_dir_path(__FILE__));
define('CLIENT_BLOCKS_URL', plugin_dir_url(__FILE__));

require_once CLIENT_BLOCKS_PATH . 'vendor/autoload.php';

// Load core files
require_once CLIENT_BLOCKS_PATH . 'src/Plugin.php';
require_once CLIENT_BLOCKS_PATH . 'src/PostType/BlockPostType.php';
require_once CLIENT_BLOCKS_PATH . 'src/Blocks/Registry/BlockRegistrar.php';
require_once CLIENT_BLOCKS_PATH . 'src/Admin/Editor/EditorPage.php';
require_once CLIENT_BLOCKS_PATH . 'src/API/RestController.php';

// Initialize the plugin
add_action('plugins_loaded', function () {
    ClientBlocks\Plugin::instance();
});
