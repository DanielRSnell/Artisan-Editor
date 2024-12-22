<?php
namespace ClientBlocks\Admin;

class Menu {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'register']);
        add_filter('parent_file', [$this, 'fix_menu_highlight']);
    }
    
    public function register() {
        add_menu_page(
            'Client Blocks',
            'Client Blocks',
            'manage_options',
            'client-blocks',
            [$this, 'render_main_page'],
            'dashicons-screenoptions',
            30
        );
        
        $this->add_submenus();
        
        // Add hidden editor page
        add_submenu_page(
            null, // Hidden from menu
            'Block Editor',
            'Block Editor',
            'manage_options',
            'client-blocks-editor',
            [$this, 'render_editor_page']
        );
    }
    
    private function add_submenus() {
        add_submenu_page(
            'client-blocks',
            'All Blocks',
            'All Blocks',
            'manage_options',
            'edit.php?post_type=client_blocks'
        );
        
        add_submenu_page(
            'client-blocks',
            'Add New Block',
            'Add New Block',
            'manage_options',
            'post-new.php?post_type=client_blocks'
        );
        
        add_submenu_page(
            'client-blocks',
            'Block Categories',
            'Block Categories',
            'manage_options',
            'edit-tags.php?taxonomy=block_categories&post_type=client_blocks'
        );
    }
    
    public function render_main_page() {
        \Timber::render('admin/dashboard.twig', [
            'title' => 'Client Blocks',
            'description' => 'Manage your custom client blocks here.'
        ]);
    }
    
    public function render_editor_page() {
        $block_id = isset($_GET['block_id']) ? intval($_GET['block_id']) : 0;
        $block = get_post($block_id);
        
        if (!$block || $block->post_type !== 'client_blocks') {
            wp_die('Invalid block ID');
        }
        
        include_once dirname(dirname(dirname(__FILE__))) . '/views/editor/layout.php';
    }
    
    public function fix_menu_highlight($parent_file) {
        global $submenu_file, $current_screen, $pagenow;
        
        if ($current_screen->post_type === 'client_blocks') {
            if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
                $submenu_file = 'edit.php?post_type=client_blocks';
            }
            $parent_file = 'client-blocks';
        }
        
        return $parent_file;
    }
}
