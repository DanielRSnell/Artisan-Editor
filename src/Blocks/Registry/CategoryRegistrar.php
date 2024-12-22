<?php
namespace ClientBlocks\Blocks\Registry;

class CategoryRegistrar {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_block_categories']);
    }
    
    public function register_block_categories() {
        $categories = get_terms([
            'taxonomy' => 'block_categories',
            'hide_empty' => false,
        ]);
        
        if (empty($categories) || is_wp_error($categories)) {
            return;
        }
        
        add_filter('block_categories_all', function($block_categories) use ($categories) {
            foreach ($categories as $category) {
                $block_categories[] = [
                    'slug' => $category->slug,
                    'title' => $category->name,
                    'icon' => null
                ];
            }
            return $block_categories;
        });
    }
}
