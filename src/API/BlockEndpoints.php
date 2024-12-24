<?php
namespace ClientBlocks\API;

use WP_Error;
use WP_REST_Request;

class BlockEndpoints
{
    public static function get_block(WP_REST_Request $request)
    {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        return self::format_block($block);
    }

    public static function update_block(WP_REST_Request $request)
    {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;

        $fields = ['php', 'template', 'js', 'css'];
        foreach ($fields as $field) {
            $content = $request->get_param($field);
            if ($content !== null) {
                $file_name = $field === 'template' ? 'block.twig' : 'block.' . $field;
                file_put_contents($block_dir . '/' . $file_name, $content);
            }
        }

        return self::format_block($block);
    }

    public static function global_save_block(WP_REST_Request $request)
    {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;
        $global_css_file = $upload_dir['basedir'] . '/client-blocks/global/raw.css';

        $fields = ['php', 'template', 'js', 'css', 'global-css'];
        foreach ($fields as $field) {
            $content = $request->get_param($field);
            if ($content !== null) {
                if ($field === 'global-css') {
                    file_put_contents($global_css_file, $content);
                } else {
                    $file_name = $field === 'template' ? 'block.twig' : 'block.' . $field;
                    file_put_contents($block_dir . '/' . $file_name, $content);
                }
            }
        }

        return self::format_block($block);
    }

    public static function format_block($block)
    {
        if (!$block) {
            return null;
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;
        $global_css_file = $upload_dir['basedir'] . '/client-blocks/global/raw.css';

        return [
            'id' => $block->ID,
            'title' => $block->post_title,
            'slug' => $slug,
            'status' => $block->post_status,
            'modified' => $block->post_modified,
            'fields' => [
                'php' => file_get_contents($block_dir . '/block.php'),
                'template' => file_get_contents($block_dir . '/block.twig'),
                'js' => file_get_contents($block_dir . '/block.js'),
                'css' => file_get_contents($block_dir . '/block.css'),
            ],
            'global-css' => file_get_contents($global_css_file),
        ];
    }
}
