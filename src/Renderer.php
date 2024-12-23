<?php
namespace ClientBlocks;

use Timber\Timber;

class Renderer
{
    public static function render($block, $content = '', $is_preview = false, $post_id = 0)
    {
        $block_name = str_replace('acf/', '', $block['name']);
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $block_name;

        $context = self::get_context($block, $content, $is_preview, $post_id, $block_dir);

        try {
            $template_file = $block_dir . '/template.twig';
            $rendered_content = Timber::compile($template_file, $context);

            if ($is_preview) {
                self::render_preview($rendered_content, $context['block']);
            } else {
                echo $rendered_content;
            }
        } catch (\Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering block: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public static function get_context($block, $content, $is_preview, $post_id, $block_dir)
    {
        $context = Timber::context();

        $context['block'] = $block;
        $context['block']['post_id'] = $post_id;
        $context['block']['is_preview'] = $is_preview;
        $context['block']['inner_blocks'] = $content;

        $context['fields'] = get_fields();

        $php_file = $block_dir . '/' . basename($block_dir) . '.php';
        if (file_exists($php_file)) {
            $block_context = include $php_file;
            if (is_array($block_context)) {
                $context = array_merge($context, $block_context);
            }
        }

        return $context;
    }

    private static function render_preview($content, $block)
    {
        $preview_styles = self::get_preview_styles();
        $preview_bar = self::get_preview_bar($block);

        echo $preview_styles;
        echo $preview_bar;
        echo '<div class="client-blocks-preview-content">';
        echo $content;
        echo '</div>';
    }

    private static function get_preview_styles()
    {
        return '<style>
            .client-blocks-preview-bar {
                background: #1e1e1e;
                color: #fff;
                padding: 8px 12px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                font-size: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 4px 4px 0 0;
            }
            .client-blocks-preview-content {
                border: 1px solid #e2e4e7;
                border-top: none;
                border-radius: 0 0 4px 4px;
                padding: 1px;
            }
        </style>';
    }

    private static function get_preview_bar($block)
    {
        return sprintf(
            '<div class="client-blocks-preview-bar">
                <div>Block: %s</div>
                <div>Preview Mode</div>
            </div>',
            esc_html($block['title'])
        );
    }
}
