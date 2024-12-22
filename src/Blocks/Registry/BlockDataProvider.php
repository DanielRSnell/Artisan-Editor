<?php
namespace ClientBlocks\Blocks\Registry;

use ClientBlocks\Blocks\BlockDefaults;

class BlockDataProvider
{
    public static function get_block_data($block)
    {
        return [
            'template_id' => $block->ID,
            'php' => get_post_meta($block->ID, '_client_php', true) ?: BlockDefaults::get_default_php(),
            'template' => get_post_meta($block->ID, '_client_template', true) ?: BlockDefaults::get_default_template(),
            'js' => get_post_meta($block->ID, '_client_js', true) ?: BlockDefaults::get_default_js(),
            'css' => get_post_meta($block->ID, '_client_css', true) ?: BlockDefaults::get_default_css(),
            'mock_fields' => get_fields($block->ID),
        ];
    }

    public static function get_block_category($block)
    {
        $categories = wp_get_post_terms($block->ID, 'block_categories');
        return !empty($categories) ? $categories[0]->slug : 'common';
    }
}
