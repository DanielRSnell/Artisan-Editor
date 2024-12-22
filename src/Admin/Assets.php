<?php
namespace ClientBlocks\Admin;

class Assets {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    public function enqueue_styles() {
        if (!$this->is_plugin_page()) {
            return;
        }
        
        wp_enqueue_style(
            'client-blocks-admin',
            plugins_url('assets/css/admin.css', dirname(__DIR__))
        );
    }
    
    private function is_plugin_page() {
        global $current_screen;
        return $current_screen && $current_screen->post_type === 'client_blocks';
    }
}
