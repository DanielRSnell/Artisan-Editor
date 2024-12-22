<?php
namespace ClientBlocks\Blocks;

use Timber\Timber;

class BlockContext
{
    public static function get_context()
    {
        $context = Timber::context();

        // Add global post data if available
        global $post;

        // Handle different page types
        if (is_singular()) {
            $context['is_singular'] = true;
            $context['object_type'] = 'singular';
            $context['object'] = get_queried_object();
        } elseif (is_archive()) {
            $context['is_archive'] = true;
            $context['object_type'] = 'archive';
            $context['object'] = get_queried_object();

            if (is_post_type_archive()) {
                $context['archive_type'] = 'post_type';
            } elseif (is_tax() || is_category() || is_tag()) {
                $context['archive_type'] = 'taxonomy';
                $context['term'] = get_queried_object();
            }
        } elseif (is_search()) {
            $context['is_search'] = true;
            $context['object_type'] = 'search';
            $context['search_query'] = get_search_query();
        } elseif (is_home()) {
            $context['is_home'] = true;
            $context['object_type'] = 'home';
            $context['page_for_posts'] = get_option('page_for_posts');
        }

        // Add WooCommerce specific context if active
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            $context['is_woocommerce'] = true;
            if (is_product()) {
                global $product;
                $context['product'] = $product;
            }
        }

        return $context;
    }
}
