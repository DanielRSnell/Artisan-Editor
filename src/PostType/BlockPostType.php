<?php
namespace ClientBlocks\PostType;

class BlockPostType
{
    private static $instance = null;
    private $blocks_dir;
    private $template_dir;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->blocks_dir = $upload_dir['basedir'] . '/client-blocks/blocks';
        $this->template_dir = CLIENT_BLOCKS_PATH . 'assets/block';

        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'add_support_meta_box']);
        add_action('save_post_client_blocks', [$this, 'save_support_options']);
        add_action('save_post_client_blocks', [$this, 'save_block_files'], 10, 3);
        add_action('before_delete_post', [$this, 'delete_block_files']);
        add_filter('post_row_actions', [$this, 'modify_list_row_actions'], 10, 2);
        add_action('edit_form_after_title', [$this, 'add_editor_button_to_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    public function register()
    {
        register_post_type('client_blocks', [
            'labels' => $this->get_labels(),
            'public' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => ['slug' => 'client-block'],
            'menu_icon' => 'dashicons-block-default',
        ]);
    }

    public function add_support_meta_box()
    {
        add_meta_box(
            'block_support_options',
            'Block Support Options',
            [$this, 'render_support_meta_box'],
            'client_blocks',
            'side',
            'default'
        );
    }

    public function render_support_meta_box($post)
    {
        wp_nonce_field('block_support_options', 'block_support_options_nonce');

        $supports_inner_blocks = get_post_meta($post->ID, '_supports_inner_blocks', true);
        $supports_align = get_post_meta($post->ID, '_supports_align', true);
        $supports_align_text = get_post_meta($post->ID, '_supports_align_text', true);
        $supports_align_content = get_post_meta($post->ID, '_supports_align_content', true);

        ?>
        <p>
            <label>
                <input type="checkbox" name="supports_inner_blocks" value="1" <?php checked($supports_inner_blocks, '1');?>>
                Support Inner Blocks
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align" value="1" <?php checked($supports_align, '1');?>>
                Support Block Alignment
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align_text" value="1" <?php checked($supports_align_text, '1');?>>
                Support Text Alignment
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="supports_align_content" value="1" <?php checked($supports_align_content, '1');?>>
                Support Content Alignment
            </label>
        </p>
        <?php
}

    public function save_support_options($post_id)
    {
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
            'supports_align_content',
        ];

        foreach ($support_options as $option) {
            update_post_meta(
                $post_id,
                '_' . $option,
                isset($_POST[$option]) ? '1' : '0'
            );
        }
    }

    public function save_block_files($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type != 'client_blocks') {
            return;
        }

        $block_slug = sanitize_title($post->post_title);
        $block_dir = $this->blocks_dir . '/' . $block_slug;

        if (!file_exists($block_dir)) {
            wp_mkdir_p($block_dir);
        }

        $this->create_block_json($block_dir, $post, $block_slug);
        $this->create_php_file($block_dir, $block_slug);
        $this->create_twig_file($block_dir);
        $this->create_js_file($block_dir);
        $this->create_css_file($block_dir);
    }

    public function delete_block_files($post_id)
    {
        $post = get_post($post_id);
        if ($post->post_type != 'client_blocks') {
            return;
        }

        $block_slug = sanitize_title($post->post_title);
        $block_dir = $this->blocks_dir . '/' . $block_slug;

        if (file_exists($block_dir)) {
            $this->recursive_rmdir($block_dir);
        }
    }

    private function recursive_rmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                        $this->recursive_rmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }

                }
            }
            rmdir($dir);
        }
    }

    private function create_block_json($block_dir, $post, $block_slug)
    {
        $template = file_get_contents($this->template_dir . '/block.json');
        $content = str_replace(
            ['{{block_name}}', '{{block_title}}', '{{block_description}}', '{{block_slug}}'],
            [$block_slug, $post->post_title, $post->post_excerpt, $block_slug],
            $template
        );
        file_put_contents($block_dir . '/block.json', $content);
    }

    private function create_php_file($block_dir, $block_slug)
    {
        $template = file_get_contents($this->template_dir . '/block.php');
        $content = str_replace('{{block_slug}}', $block_slug, $template);
        file_put_contents($block_dir . '/' . 'block.php', $content);
    }

    private function create_twig_file($block_dir)
    {
        copy($this->template_dir . '/block.twig', $block_dir . '/block.twig');
    }

    private function create_js_file($block_dir)
    {
        copy($this->template_dir . '/block.js', $block_dir . '/block.js');
    }

    private function create_css_file($block_dir)
    {
        copy($this->template_dir . '/block.css', $block_dir . '/block.css');
    }

    public function modify_list_row_actions($actions, $post)
    {
        if ($post->post_type === 'client_blocks') {
            $editor_url = add_query_arg([
                'artisan' => 'editor',
                'block_id' => $post->ID,
            ], get_permalink($post->ID));

            $actions['edit_in_artisan'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($editor_url),
                __('Edit in Artisan', 'client-blocks')
            );
        }
        return $actions;
    }

    public function add_editor_button_to_post($post)
    {
        if ($post->post_type !== 'client_blocks') {
            return;
        }

        $editor_url = add_query_arg([
            'artisan' => 'editor',
            'block_id' => $post->ID,
        ], get_permalink($post->ID));

        $plugin_url = plugin_dir_url(dirname(__DIR__)) . 'assets/images/artisan-backdrop.png';

        ?>
        <div class="client_block_fields">
            <style>
                .artisan-editor-container {
                    width: 100%;
                    height: 500px;
                    margin: 20px auto;
                    position: relative;
                    border-radius: 4px;
                    overflow: hidden;
                    background-image: url('<?php echo esc_url($plugin_url); ?>');
                    background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                }
                .artisan-editor-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.3s ease;
                }
                .artisan-editor-container:hover .artisan-editor-overlay {
                    background: rgba(0, 0, 0, 0.5);
                }
                .artisan-editor-button {
                    display: inline-block;
                    padding: 15px 25px;
                    font-size: 16px;
                    font-weight: 600;
                    text-decoration: none;
                    background: #2271b1;
                    color: #fff;
                    border-radius: 3px;
                    transition: background-color 0.2s ease;
                    z-index: 2;
                }
                .artisan-editor-button:hover {
                    background: #135e96;
                    color: #fff;
                }
            </style>
            <div class="artisan-editor-container">
                <div class="artisan-editor-overlay">
                    <a href="<?php echo esc_url($editor_url); ?>" class="artisan-editor-button">
                        Open in Artisan Editor
                    </a>
                </div>
            </div>
        </div>
        <?php
}

    public function enqueue_admin_styles()
    {
        wp_enqueue_style(
            'client-blocks-admin',
            plugin_dir_url(dirname(__DIR__)) . 'assets/css/admin.css',
            [],
            filemtime(plugin_dir_path(dirname(__DIR__)) . 'assets/css/admin.css')
        );
    }

    private function get_labels()
    {
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
            'not_found_in_trash' => 'No client blocks found in trash',
        ];
    }
}
