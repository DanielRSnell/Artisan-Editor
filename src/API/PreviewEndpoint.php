<?php
namespace ClientBlocks\API;

use Timber\Timber;
use WP_REST_Request;

class PreviewEndpoint
{
    public static function render_preview(WP_REST_Request $request)
    {
        try {
            $data = $request->get_params();

            $context = [];

            if (!empty($data['php'])) {
                ob_start();
                // extract context into eval scope
                extract($context);
                $php_result = eval('?>' . $data['php']);
                ob_end_clean();
                if (is_array($php_result)) {
                    $context = array_merge($context, $php_result);
                }
            }

            $block = [
                'id' => $data['block_id'],
                'name' => 'acf/' . get_post($data['block_id'])->post_name,
                'is_preview' => true,
                'align' => $data['align'] ?? '',
                'className' => $data['className'] ?? '',
                'mode' => $data['mode'] ?? 'preview',
                'supports' => $data['supports'] ?? [],
            ];

            $context['block'] = $block;

            $preview_context = $data['preview_context'] ?? null;

            // if $context['preview'] is not set run get_base_context
            if (!isset($context['preview'])) {
                $context = get_base_context($preview_context);
            }

            $content = Timber::compile_string($data['template'], $context);
            $wrapped_content = self::wrap_block_markup($content, $block, $data);

            return rest_ensure_response([
                'content' => $wrapped_content,
                'context' => $context,
                'preview_info' => $preview_context,
            ]);

        } catch (\Exception $e) {
            return new \WP_Error('preview_error', $e->getMessage(), ['status' => 500]);
        }
    }

    private static function wrap_block_markup($content, $block, $data)
    {
        $block_id = $block['id'];
        $block_name = str_replace('acf/', '', $block['name']);

        $classes = array_filter([
            'wp-block-acf-' . $block_name,
            'acf-block',
            'is-preview',
            !empty($data['align']) ? 'align' . $data['align'] : '',
            !empty($data['className']) ? $data['className'] : '',
        ]);

        $output = [];
        $output[] = sprintf(
            '<div id="block-%s" class="%s" data-block="%s" data-name="%s" data-preview="true">',
            esc_attr($block_id),
            esc_attr(implode(' ', $classes)),
            esc_attr($block_id),
            esc_attr($block_name)
        );

        if (!empty($data['css'])) {
            $output[] = sprintf(
                '<style>#block-%s {%s}</style>',
                esc_attr($block_id),
                $data['css']
            );
        }

        $output[] = $content;

        if (!empty($data['js'])) {
            $output[] = sprintf(
                '<script>(function(){const block=document.getElementById("block-%s");if(!block)return;%s})();</script>',
                esc_attr($block_id),
                $data['js']
            );
        }

        $output[] = '</div>';

        return implode("\n", $output);
    }
}

function get_base_context($preview_context)
{
    $context = Timber::context();

    $context['preview'] = [
        'raw' => $preview_context,
        'status' => 'no_context',
        'debug' => false,
    ];

    if (!$preview_context) {
        return $context;
    }

    switch ($preview_context['type']) {
        case 'single':
            if (!empty($preview_context['post_id'])) {
                global $post;
                $post = get_post($preview_context['post_id']);
                setup_postdata($post);

                $context['post'] = Timber::get_post($preview_context['post_id']);
                $context['fields'] = get_fields($preview_context['post_id']);
                $context['post_type'] = get_post_type($preview_context['post_id']);

                $context['preview']['status'] = 'single_success';
                $context['preview']['debug'] = true;
            } else {
                $context['preview']['status'] = 'single_missing_post_id';
            }
            break;

        case 'archive':
            if (!empty($preview_context['post_type'])) {
                $context['posts'] = Timber::get_posts([
                    'post_type' => $preview_context['post_type'],
                    'posts_per_page' => 10,
                ]);

                $post_type_obj = get_post_type_object($preview_context['post_type']);
                $context['archive'] = [
                    'title' => $post_type_obj->labels->name,
                    'description' => $post_type_obj->description,
                    'post_type' => $preview_context['post_type'],
                ];

                $context['preview']['status'] = 'archive_success';
                $context['preview']['debug'] = true;
            } else {
                $context['preview']['status'] = 'archive_missing_post_type';
            }
            break;

        case 'taxonomy':
            if (!empty($preview_context['term_id'])) {
                $context['term'] = Timber::get_term($preview_context['term_id']);

                if ($context['term']) {
                    $context['posts'] = Timber::get_posts([
                        'tax_query' => [[
                            'taxonomy' => $context['term']->taxonomy,
                            'field' => 'id',
                            'terms' => $context['term']->ID,
                        ]],
                        'posts_per_page' => 10,
                    ]);

                    $context['archive'] = [
                        'title' => $context['term']->name,
                        'description' => $context['term']->description,
                    ];

                    $context['preview']['status'] = 'taxonomy_success';
                    $context['preview']['debug'] = true;
                } else {
                    $context['preview']['status'] = 'taxonomy_term_not_found';
                }
            } else {
                $context['preview']['status'] = 'taxonomy_missing_term_id';
            }
            break;

        case 'wc_shop':
            if (function_exists('WC')) {
                $context['products'] = Timber::get_posts([
                    'post_type' => 'product',
                    'posts_per_page' => 10,
                ]);

                $shop_page_id = wc_get_page_id('shop');
                if ($shop_page_id) {
                    $context['shop'] = Timber::get_post($shop_page_id);
                    $context['preview']['status'] = 'wc_shop_success';
                    $context['preview']['debug'] = true;
                } else {
                    $context['preview']['status'] = 'wc_shop_page_not_found';
                }
            } else {
                $context['preview']['status'] = 'wc_not_active';
            }
            break;

        case 'wc_product':
            if (function_exists('WC') && !empty($preview_context['post_id'])) {
                global $post;
                $post = get_post($preview_context['post_id']);
                setup_postdata($post);

                $context['product'] = Timber::get_post($preview_context['post_id']);
                $context['fields'] = get_fields($preview_context['post_id']);

                $context['preview']['status'] = 'wc_product_success';
                $context['preview']['debug'] = true;
            } else {
                $context['preview']['status'] = function_exists('WC') ?
                'wc_product_missing_id' :
                'wc_not_active';
            }
            break;

        default:
            $context['preview']['status'] = 'unknown_type';
            break;
    }

    return $context;
}
