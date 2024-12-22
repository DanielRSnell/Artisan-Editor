<?php
namespace ClientBlocks\Blocks\Registry;

use ClientBlocks\Blocks\Support\BlockSupports;
use Timber\Timber;

class BlockRegistrar
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_gutenberg_script']);

    }

    public function register_blocks()
    {
        $blocks = BlockFetcher::get_blocks();

        foreach ($blocks as $block) {
            $this->register_block($block);
        }
    }

    private function register_block($block)
    {
        $block_data = BlockDataProvider::get_block_data($block);
        $block_name = sanitize_title($block->post_title);

        acf_register_block_type([
            'name' => $block_name,
            'title' => $block->post_title,
            'description' => $block->post_excerpt,
            'category' => BlockDataProvider::get_block_category($block),
            'icon' => 'screenoptions',
            'keywords' => [],
            'render_callback' => function ($block, $content = '', $is_preview = false, $post_id = 0) use ($block_data, $block_name) {
                $block['template_id'] = $block_data['template_id'];
                BlockRenderer::render($block, $content, $is_preview, $post_id, $block_data, $block_name);
            },
            'supports' => BlockSupports::get_supports($block),
            'example' => array(
                'attributes' => array(
                    'mode' => 'preview',
                    'data' => array(
                        'example' => true,
                        'mock_fields' => get_fields($block->ID),
                    ),
                ),
            ),
        ]);
    }

    public function enqueue_gutenberg_script()
    {
        wp_enqueue_script(
            'client-blocks-gutenberg-editor',
            CLIENT_BLOCKS_URL . 'assets/js/gutenberg-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor'],
            filemtime(CLIENT_BLOCKS_PATH . 'assets/js/gutenberg-editor.js'),
            true
        );

        // Get Timber context and localize it
        $context = Timber::context();

        // Print context data in a script tag
        wp_add_inline_script(
            'client-blocks-gutenberg-editor',
            sprintf(
                '<script id="gb-editor-context" type="application/json">%s</script>',
                wp_json_encode($context)
            ),
            'before'
        );

    }
}
