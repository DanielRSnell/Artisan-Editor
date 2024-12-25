<?php
namespace ClientBlocks\API;

use WP_REST_Response;
use WP_Error;

class PreviewContextEndpoint {
    public static function get_preview_contexts() {
        try {
            $contexts = [
                'post_types' => self::get_post_type_contexts(),
                'taxonomies' => self::get_taxonomy_contexts()
            ];

            if (class_exists('WooCommerce')) {
                $contexts['woocommerce'] = self::get_woocommerce_contexts();
            }

            return rest_ensure_response($contexts);
        } catch (\Exception $e) {
            return new WP_Error(
                'preview_contexts_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    private static function get_post_type_contexts() {
        $contexts = [];
        $post_types = get_post_types(['public' => true], 'objects');

        foreach ($post_types as $post_type) {
            if ($post_type->name === 'product') {
                continue;
            }

            $items = [];

            // Add archive context
            if ($post_type->has_archive) {
                $items[] = [
                    'label' => sprintf('All %s', $post_type->label),
                    'type' => 'archive',
                    'post_type' => $post_type->name
                ];
            }

            // Add recent posts
            $recent_posts = get_posts([
                'post_type' => $post_type->name,
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            foreach ($recent_posts as $post) {
                $items[] = [
                    'label' => $post->post_title,
                    'type' => 'single',
                    'post_type' => $post_type->name,
                    'post_id' => $post->ID
                ];
            }

            if (!empty($items)) {
                $contexts[$post_type->name] = [
                    'label' => $post_type->label,
                    'items' => $items
                ];
            }
        }

        return $contexts;
    }

    private static function get_taxonomy_contexts() {
        $contexts = [];
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        foreach ($taxonomies as $tax) {
            if (in_array($tax->name, ['product_cat', 'product_tag'])) {
                continue;
            }

            $terms = get_terms([
                'taxonomy' => $tax->name,
                'hide_empty' => true,
                'number' => 10
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                $terms_data = [];
                foreach ($terms as $term) {
                    $terms_data[] = [
                        'label' => $term->name,
                        'type' => 'taxonomy',
                        'taxonomy' => $tax->name,
                        'term_id' => $term->term_id,
                        'count' => $term->count
                    ];
                }

                $contexts[$tax->name] = [
                    'label' => $tax->label,
                    'terms' => $terms_data
                ];
            }
        }

        return $contexts;
    }

    private static function get_woocommerce_contexts() {
        if (!function_exists('WC')) {
            return [];
        }

        $contexts = [
            'shop' => [
                'label' => 'Shop Page',
                'type' => 'wc_shop'
            ],
            'products' => []
        ];

        // Recent products
        $products = wc_get_products([
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($products as $product) {
            $contexts['products'][] = [
                'label' => $product->get_name(),
                'type' => 'wc_product',
                'post_id' => $product->get_id()
            ];
        }

        return $contexts;
    }
}
