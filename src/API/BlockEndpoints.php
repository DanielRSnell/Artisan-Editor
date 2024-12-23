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

        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . sanitize_title($block->post_title);

        $files = [
            'block.json' => 'block_json',
            sanitize_title($block->post_title) . '.php' => 'php',
            'template.twig' => 'template',
            'styles.css' => 'styles',
            'scripts.js' => 'scripts',
        ];

        foreach ($files as $file => $param) {
            $content = $request->get_param($param);
            if ($content !== null) {
                file_put_contents($block_dir . '/' . $file, $content);
            }
        }

        return self::get_block($request);
    }

    private static function format_block($block)
    {
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . sanitize_title($block->post_title);

        return [
            'id' => $block->ID,
            'title' => $block->post_title,
            'slug' => $block->post_name,
            'status' => $block->post_status,
            'modified' => $block->post_modified,
            'files' => [
                'block_json' => file_get_contents($block_dir . '/block.json'),
                'php' => file_get_contents($block_dir . '/' . sanitize_title($block->post_title) . '.php'),
                'template' => file_get_contents($block_dir . '/template.twig'),
                'styles' => file_get_contents($block_dir . '/styles.css'),
                'scripts' => file_get_contents($block_dir . '/scripts.js'),
            ],
        ];
    }
}
