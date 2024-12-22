<?php
namespace ClientBlocks\API;

class CategoryEndpoints {
    public static function get_categories(\WP_REST_Request $request) {
        $categories = get_terms([
            'taxonomy' => 'block_categories',
            'hide_empty' => false,
        ]);
        
        if (is_wp_error($categories)) {
            return new \WP_Error(
                'categories_error',
                'Error fetching categories',
                ['status' => 500]
            );
        }
        
        return array_map([self::class, 'format_category'], $categories);
    }
    
    private static function format_category($category) {
        return [
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'count' => $category->count,
        ];
    }
}
