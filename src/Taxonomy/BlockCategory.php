<?php
namespace ClientBlocks\Taxonomy;

class BlockCategory {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register']);
    }
    
    public function register() {
        register_taxonomy('block_categories', ['client_blocks'], [
            'labels' => $this->get_labels(),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'block-category'],
        ]);
    }
    
    private function get_labels() {
        return [
            'name' => 'Block Categories',
            'singular_name' => 'Block Category',
            'search_items' => 'Search Block Categories',
            'all_items' => 'All Block Categories',
            'parent_item' => 'Parent Block Category',
            'parent_item_colon' => 'Parent Block Category:',
            'edit_item' => 'Edit Block Category',
            'update_item' => 'Update Block Category',
            'add_new_item' => 'Add New Block Category',
            'new_item_name' => 'New Block Category Name',
            'menu_name' => 'Block Categories'
        ];
    }
}
