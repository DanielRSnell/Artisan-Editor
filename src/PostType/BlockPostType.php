<?php
namespace ClientBlocks\PostType;

class BlockPostType {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'add_support_meta_box']);
        add_action('save_post_client_blocks', [$this, 'save_support_options']);
        add_filter('single_template', [$this, 'set_single_template']);
    }
    
    public function register() {
        register_post_type('client_blocks', [
            'labels' => $this->get_labels(),
            'public' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => ['slug' => 'client-block'],
        ]);
    }
    
    public function add_support_meta_box() {
        add_meta_box(
            'block_support_options',
            'Block Support Options',
            [$this, 'render_support_meta_box'],
            'client_blocks',
            'side',
            'default'
        );
    }
    
    public function render_support_meta_box($post) {
        wp_nonce_field('block_support_options', 'block_support_options_nonce');
        
        $supports_inner_blocks = get_post_meta($post->ID, '_supports_inner_blocks', true);
        $supports_align = get_post_meta($post->ID, '_supports_align', true);
        $supports_align_text = get_post_meta($post->ID, '_supports_align_text', true);
        $supports_align_content = get_post_meta($post->ID, '_supports_align_content', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="supports_inner_blocks" value="1" <?php checked($supports_inner_blocks, '1'); ?>>
                Support Inner Blocks
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align" value="1" <?php checked($supports_align, '1'); ?>>
                Support Block Alignment
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align_text" value="1" <?php checked($supports_align_text, '1'); ?>>
                Support Text Alignment
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align_content" value="1" <?php checked($supports_align_content, '1'); ?>>
                Support Content Alignment
            </label>
        </p>
        <?php
    }
    
    public function save_support_options($post_id) {
        if (!isset($_POST['block_support_options_nonce']) || 
            !wp_verify_nonce($_POST['block_support_options_nonce'], 'block_support_options')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $support_options = [
            'supports_inner_blocks',
            'supports_align',
            'supports_align_text',
            'supports_align_content'
        ];
        
        foreach ($support_options as $option) {
            update_post_meta(
                $post_id,
                '_' . $option,
                isset($_POST[$option]) ? '1' : '0'
            );
        }
    }

    public function set_single_template($single_template) {
        global $post;

        if ($post->post_type === 'client_blocks') {
            if (isset($_GET['artisan']) && $_GET['artisan'] === 'true' && isset($_GET['block_id'])) {
                $single_template = CLIENT_BLOCKS_PATH . 'views/editor/layout.php';
            } else {
                $single_template = CLIENT_BLOCKS_PATH . 'views/editor/preview.php';
            }
        }

        return $single_template;
    }
    
    private function get_labels() {
        return [
            'name' => 'Client Blocks',
            'singular_name' => 'Client Block',
            'menu_name' => 'Client Blocks',
            'add_new' => 'Add New Block',
            'add_new_item' => 'Add New Client Block',
            'edit_item' => 'Edit Client Block',
            'new_item' => 'New Client Block',
            'view_item' => 'View Client Block',
            'search_items' => 'Search Client Blocks',
            'not_found' => 'No client blocks found',
            'not_found_in_trash' => 'No client blocks found in trash'
        ];
    }
}
