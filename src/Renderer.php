<?php
namespace ClientBlocks;

use Timber\Timber;

class Renderer
{
    public static function render($block, $content = '', $is_preview = false, $post_id = 0, $block_data = null)
    {
        try {
            $context = self::get_context($block, $content, $is_preview, $post_id, $block_data);
            $template = self::prepare_template($block_data, $block);
            $template = do_shortcode($template);
            $rendered_content = Timber::compile_string($template, $context);

            if ($is_preview) {
                self::render_preview($rendered_content, $context['block']);
            } else {
                echo $rendered_content;
            }
        } catch (\Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering block: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public static function get_context($block, $content, $is_preview, $post_id, $block_data)
    {
        global $wp_query;
        global $post;
        $context = Timber::context();

        if (!$wp_query->have_posts()) {
            $context['posts'] = false;
        } else {
            $context['posts'] = Timber::get_posts($wp_query);
        }

        if (!$post) {
            $context['post'] = false;
        } else {
            $context['post'] = Timber::get_post($post->ID);
        }

        $context['block'] = array_merge($block, [
            'name' => $block['name'] ?? '',
            'post_id' => $post_id,
            'is_preview' => $is_preview,
            'inner_blocks' => $content,
        ]);

        $current_url = home_url($_SERVER['REQUEST_URI']);
        $url_parts = parse_url($current_url);
        $query_params = [];

        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $query_params);
        }

        $context['current'] = [
            'post_type' => get_post_type(),
            'url' => [
                'full' => $current_url,
                'slug' => trim($url_parts['path'], '/'),
                'params' => $query_params,
            ],
        ];

        $context['fields'] = $is_preview && isset($block['data']['mock_fields'])
        ? $block['data']['mock_fields']
        : get_fields();

        $context['attributes'] = $block['data'] ?? [];

        if (!empty($block_data['php'])) {
            $php_context = self::execute_php($block_data['php'], $context, $context['block']);
            if (is_array($php_context)) {
                $context = array_merge($context, $php_context);
            }
        }

        return $context;
    }

    private static function execute_php($code, $context, $block)
    {
        try {
            extract(['context' => $context]);

            return eval('?>' . $code);
        } catch (\Throwable $e) {
            error_log('Block PHP execution error: ' . $e->getMessage());
            return ['error' => 'Error executing block PHP code'];
        }
    }

    private static function prepare_template($block_data, $block)
    {
        $template = $block_data['template'];

        if (!empty($block_data['css'])) {
            $css = str_replace('.example-block', '#block-' . esc_attr($block['id']), $block_data['css']);
            $template = '<style>' . $css . '</style>' . $template;
        }

        if (!empty($block_data['js'])) {
            $js = str_replace('{{ block.id }}', 'block-' . esc_attr($block['id']), $block_data['js']);
            $template .= '<script>' . $js . '</script>';
        }

        return $template;
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
        return '
            <style>
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
                .client-blocks-preview-bar code {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 2px 6px;
                    border-radius: 3px;
                    margin: 0 4px;
                    font-family: Monaco, Consolas, "Andale Mono", "DejaVu Sans Mono", monospace;
                }
                .client-blocks-preview-content {
                    border: 1px solid #e2e4e7;
                    border-top: none;
                    border-radius: 0 0 4px 4px;
                    padding: 1px;
                }
                .open-editor-button {
                    background: #007cba;
                    border: none;
                    color: #fff;
                    padding: 4px 8px;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 12px;
                }
                .open-editor-button:hover {
                    background: #0071a1;
                }
            </style>
        ';
    }

    private static function get_preview_bar($block)
    {
        $post = get_post($block['template_id']);
        $object = [
            'id' => $post->ID,
            'name' => $post->post_type,
            'permalink' => get_post_permalink($post->ID),
        ];
        $object = htmlspecialchars(json_encode($object), ENT_QUOTES, 'UTF-8');
        $open_editor_button = '<button class="open-editor-button" onclick="window.parent.openClientBlocksEditor(' . $object . ')">Open Artisan</button>';

        return sprintf(
            '<div class="client-blocks-preview-bar">
                <div>
                    <span>Block ID: <code>%s</code></span>
                    <span>Post ID: <code>%s</code></span>
                    <span>Type: <code>%s</code></span>
                </div>
                <div>
                    %s
                    <span>Preview Mode</span>
                </div>
            </div>',
            esc_html($block['id']),
            esc_html($block['post_id']),
            esc_html($block['name']),
            $open_editor_button
        );
    }
}
