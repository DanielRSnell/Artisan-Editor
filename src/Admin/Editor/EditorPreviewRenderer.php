<?php
namespace ClientBlocks\Admin\Editor;

use Timber\Timber;
use Timber\PostQuery;

class EditorPreviewRenderer
{
    public static function render($block_data)
    {
        try {
            ob_start();

            // Get base context
            $context = Timber::context();
            
            // Add preview context data
            $preview_context = $block_data['preview_context'] ?? null;
            if ($preview_context) {
                $context = self::add_preview_context($context, $preview_context);
            }

            // Add block data
            $context['block'] = [
                'id' => $block_data['id'],
                'name' => $block_data['name'],
                'data' => $block_data['data'],
                'align' => $block_data['align'] ?? '',
                'mode' => 'preview',
                'supports' => self::get_block_supports($block_data),
                'className' => $block_data['className'] ?? '',
                'anchor' => '',
                'is_preview' => true,
            ];

            // Execute PHP logic if present
            if (!empty($block_data['data']['php'])) {
                try {
                    ob_start();
                    $php_result = eval('?>' . $block_data['data']['php']);
                    ob_end_clean();
                    if (is_array($php_result)) {
                        $context = array_merge($context, $php_result);
                    }
                } catch (\ParseError $e) {
                    $context['php_error'] = $e->getMessage();
                }
            }

            // Compile template
            ob_start();
            $content = Timber::compile_string($block_data['data']['template'], $context);
            ob_end_clean();

            $wrapped_content = self::wrap_with_gutenberg_markup($content, $block_data, $context);

            ob_end_clean();

            return [
                'content' => $wrapped_content,
                'context' => $context,
                'preview_info' => $preview_context
            ];
        } catch (\Exception $e) {
            ob_end_clean();
            throw new \Exception('Error rendering preview: ' . $e->getMessage());
        }
    }

    private static function add_preview_context($context, $preview_context)
    {
        $type = $preview_context['type'] ?? '';
        
        switch ($type) {
            case 'single':
                if (isset($preview_context['post_id'])) {
                    $context['post'] = Timber::get_post($preview_context['post_id']);
                    $context['post_type'] = get_post_type($preview_context['post_id']);
                }
                break;

            case 'archive':
                if (isset($preview_context['post_type'])) {
                    $context['posts'] = Timber::get_posts([
                        'post_type' => $preview_context['post_type']
                    ]);
                    $post_type_obj = get_post_type_object($preview_context['post_type']);
                    $context['archive'] = [
                        'title' => $post_type_obj->label,
                        'description' => $post_type_obj->description,
                        'post_type' => $preview_context['post_type']
                    ];
                }
                break;

            case 'taxonomy':
                if (isset($preview_context['taxonomy']) && isset($preview_context['term_id'])) {
                    $context['term'] = Timber::get_term($preview_context['term_id']);
                    $context['posts'] = Timber::get_posts([
                        'tax_query' => [[
                            'taxonomy' => $preview_context['taxonomy'],
                            'field' => 'id',
                            'terms' => $preview_context['term_id']
                        ]]
                    ]);
                    $context['archive'] = [
                        'title' => $context['term']->name,
                        'description' => $context['term']->description
                    ];
                }
                break;

            case 'wc_shop':
                if (function_exists('WC')) {
                    $context['products'] = Timber::get_posts([
                        'post_type' => 'product'
                    ]);
                    $context['archive'] = [
                        'title' => 'Shop',
                        'description' => ''
                    ];
                }
                break;

            case 'wc_product':
                if (function_exists('WC') && isset($preview_context['post_id'])) {
                    $context['product'] = Timber::get_post($preview_context['post_id']);
                }
                break;
        }

        return $context;
    }

    private static function wrap_with_gutenberg_markup($content, $block_data, $context)
    {
        $block_id = $block_data['id'];
        $block_name = str_replace('acf/', '', $block_data['name']);
        $block_json = json_decode($block_data['data']['block_json'] ?? '{}', true);
        $block_settings = $block_json ?? [];

        $classes = array_filter([
            'wp-block-acf-' . $block_name,
            'acf-block',
            'is-preview',
            !empty($block_settings['align']) ? 'align' . $block_settings['align'] : '',
            !empty($block_settings['className']) ? $block_settings['className'] : '',
        ]);

        $output = [];

        $output[] = sprintf(
            '<div id="block-%s" class="%s" data-block="%s" data-name="%s" data-preview="true">',
            esc_attr($block_id),
            esc_attr(implode(' ', $classes)),
            esc_attr($block_id),
            esc_attr($block_name)
        );

        if (!empty($block_data['data']['css'])) {
            $output[] = sprintf(
                '<style>
                    #block-%s {
                        %s
                    }
                </style>',
                esc_attr($block_id),
                $block_data['data']['css']
            );
        }

        $output[] = $content;

        if (!empty($block_data['data']['js'])) {
            $output[] = sprintf(
                '<script>
                    (function() {
                        const block = document.getElementById("block-%s");
                        if (!block) return;
                        %s
                    })();
                </script>',
                esc_attr($block_id),
                $block_data['data']['js']
            );
        }

        $output[] = '</div>';

        return implode("\n", $output);
    }

    private static function get_block_supports($block_data)
    {
        return array_merge([
            'align' => true,
            'mode' => true,
            'multiple' => true,
            'jsx' => true,
            'anchor' => true,
            'customClassName' => true,
        ], $block_data['supports'] ?? []);
    }
}
