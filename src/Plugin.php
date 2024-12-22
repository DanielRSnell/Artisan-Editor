<?php
namespace ClientBlocks;

use ClientBlocks\Blocks\Registry\BlockRegistrar;
use ClientBlocks\Blocks\Registry\CategoryRegistrar;
use ClientBlocks\Admin\Editor\EditorPage;
use ClientBlocks\Admin\Editor\BreakpointManager;
use ClientBlocks\Admin\Editor\GlobalCSSManager;
use Timber\Timber;

class Plugin {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', [$this, 'init_timber']);
        add_filter('timber/locations', [$this, 'add_timber_locations']);
        add_filter('timber/twig/functions', [$this, 'add_timber_functions']);
        
        $this->init_modules();
        
        register_activation_hook(__FILE__, function() {
            add_option('client_blocks_flush_rewrite_rules', true);
        });
    }
    
    public function init_timber() {
        if (class_exists('Timber\Timber')) {
            Timber::init();
        }
    }
    
    public function add_timber_locations($paths) {
        $paths['client_blocks'] = [
            CLIENT_BLOCKS_PATH . 'views'
        ];
        return $paths;
    }
    
    public function add_timber_functions($functions) {
        $functions['acf_form'] = [
            'callable' => [$this, 'render_acf_form'],
        ];
        return $functions;
    }
    
    public function render_acf_form($post_id) {
        if (function_exists('acf_form')) {
            ob_start();
            acf_form([
                'post_id' => $post_id,
                'form' => true,
                'return' => false,
                'submit_value' => 'Update Fields'
            ]);
            return ob_get_clean();
        }
        return '';
    }
    
    private function init_modules() {
        PostType\BlockPostType::instance();
        Taxonomy\BlockCategory::instance();
        Admin\MetaBoxes::instance();
        Admin\Menu::instance();
        Admin\Assets::instance();
        
        BlockRegistrar::instance();
        CategoryRegistrar::instance();
        
        API\RestController::instance();
        
        EditorPage::instance();
        
        BreakpointManager::instance();
        GlobalCSSManager::instance();
    }
}
