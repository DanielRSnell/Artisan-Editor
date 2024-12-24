<?php
namespace ClientBlocks\Admin\Editor;

use ClientBlocks\API\BlockEndpoints;
use Timber\Timber;

class EditorPage
{
    private static $instance = null;
    private $breakpoint_manager;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->breakpoint_manager = BreakpointManager::instance();
        add_action('template_redirect', [$this, 'maybe_load_editor']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_editor_assets']);
    }

    public function maybe_load_editor()
    {
        if ($this->is_editor_page()) {
            $block_id = intval($_GET['block_id']);
            $block = get_post($block_id);

            if ($block && $block->post_type === 'client_blocks') {
                $context = Timber::context();
                $context['block'] = $block;
                $context['block_title'] = $block->post_title;
                $context['breakpoints'] = $this->breakpoint_manager->get_breakpoints();
                $context['editor_styles'] = $this->get_editor_styles();
                $context['editor_scripts'] = $this->get_editor_scripts();
                $context['client_blocks_editor_data'] = $this->get_client_blocks_editor_data($block);

                Timber::render('@client_blocks/editor/layout.twig', $context);
                exit;
            } else {
                wp_die('Invalid block ID or block type.');
            }
        }
    }

    public function enqueue_editor_assets()
    {
        if (!$this->is_editor_page()) {
            return;
        }

        foreach ($this->get_editor_styles() as $style) {
            wp_enqueue_style(
                'client-blocks-' . basename($style['href'], '.css'),
                $style['href'],
                [],
                $style['version']
            );
        }

        foreach ($this->get_editor_scripts() as $script) {
            wp_enqueue_script(
                'client-blocks-' . basename($script['src'], '.js'),
                $script['src'],
                ['jquery'],
                $script['version'],
                true
            );
        }

        $block_id = intval($_GET['block_id']);
        $block = get_post($block_id);
        if ($block && $block->post_type === 'client_blocks') {
            wp_localize_script('client-blocks-editor', 'clientBlocksEditor', $this->get_client_blocks_editor_data($block));
        }
    }

    private function get_editor_styles()
    {
        return [
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/editor.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/editor.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/header.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/header.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/sidebar.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/sidebar.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/preview.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/preview.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/breakpoints.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/breakpoints.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/topbar.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/topbar.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/monaco.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/monaco.css')],
            ['href' => CLIENT_BLOCKS_URL . 'assets/css/components/containers.css', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/css/components/containers.css')],
            ['href' => 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', 'version' => '1.12.1'],
        ];
    }

    private function get_editor_scripts()
    {
        return [
            ['src' => 'https://code.jquery.com/jquery-3.6.0.min.js', 'version' => '3.6.0'],
            ['src' => 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', 'version' => '1.12.1'],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js', 'version' => '4.17.21'],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js', 'version' => '0.44.0'],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/editor/config.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/editor/config.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/editor/status.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/editor/status.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/editor/preview.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/editor/preview.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/editor/api.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/editor/api.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/editor.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/editor.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/breakpoints.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/breakpoints.js')],
            ['src' => CLIENT_BLOCKS_URL . 'assets/js/preview.js', 'version' => filemtime(CLIENT_BLOCKS_PATH . 'assets/js/preview.js')],
        ];
    }

    private function get_client_blocks_editor_data($block)
    {
        if (!$block) {
            return [];
        }

        return [
            'restUrl' => rest_url('client-blocks/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'blockId' => $block->ID,
            'blockSlug' => $block->post_name,
            'breakpoints' => $this->breakpoint_manager->get_breakpoints(),
            'monacoPath' => 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs',
            'blockData' => BlockEndpoints::format_block($block),
        ];
    }

    private function is_editor_page()
    {
        return isset($_GET['artisan']) && $_GET['artisan'] === 'editor' && isset($_GET['block_id']);
    }
}
