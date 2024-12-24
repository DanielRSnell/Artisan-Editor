<?php
namespace ClientBlocks\API;

use WP_Error;
use WP_REST_Request;

class BlockEndpoints {
    public static function get_block(WP_REST_Request $request) {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;
        $global_css_file = $upload_dir['basedir'] . '/client-blocks/global/raw.css';
        $global_js_file = $upload_dir['basedir'] . '/client-blocks/global/scripts.js';

        wp_mkdir_p($block_dir);
        wp_mkdir_p(dirname($global_css_file));
        wp_mkdir_p(dirname($global_js_file));

        $get_file_contents = function($file) {
            if (!file_exists($file)) {
                touch($file);
                return '';
            }
            return file_get_contents($file);
        };

        return [
            'id' => $block->ID,
            'title' => $block->post_title,
            'slug' => $slug,
            'status' => $block->post_status,
            'modified' => $block->post_modified,
            'fields' => [
                'php' => $get_file_contents($block_dir . '/block.php'),
                'template' => $get_file_contents($block_dir . '/block.twig'),
                'js' => $get_file_contents($block_dir . '/block.js'),
                'css' => $get_file_contents($block_dir . '/block.css'),
                'block-json' => $get_file_contents($block_dir . '/block.json'),
            ],
            'global-css' => $get_file_contents($global_css_file),
            'global-js' => $get_file_contents($global_js_file)
        ];
    }

    public static function update_block(WP_REST_Request $request) {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;

        wp_mkdir_p($block_dir);

        $fields = ['php', 'template', 'js', 'css', 'block-json'];
        foreach ($fields as $field) {
            $content = $request->get_param($field);
            if ($content !== null) {
                switch ($field) {
                    case 'template':
                        $file_name = 'block.twig';
                        break;
                    case 'block-json':
                        $file_name = 'block.json';
                        $decoded = json_decode($content);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return new WP_Error(
                                'invalid_json',
                                'Invalid JSON provided for block.json',
                                ['status' => 400]
                            );
                        }
                        break;
                    default:
                        $file_name = 'block.' . $field;
                }
                file_put_contents($block_dir . '/' . $file_name, $content);
            }
        }

        return self::get_block($request);
    }

    public static function global_save_block(WP_REST_Request $request) {
        $block_id = $request->get_param('id');
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }

        $slug = $block->post_name;
        $upload_dir = wp_upload_dir();
        $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;
        $global_css_file = $upload_dir['basedir'] . '/client-blocks/global/raw.css';
        $global_js_file = $upload_dir['basedir'] . '/client-blocks/global/scripts.js';

        wp_mkdir_p($block_dir);
        wp_mkdir_p(dirname($global_css_file));
        wp_mkdir_p(dirname($global_js_file));

        $fields = ['php', 'template', 'js', 'css', 'block-json', 'global-css', 'global-js'];
        foreach ($fields as $field) {
            $content = $request->get_param($field);
            if ($content !== null) {
                switch ($field) {
                    case 'global-css':
                        file_put_contents($global_css_file, $content);
                        break;
                    case 'global-js':
                        file_put_contents($global_js_file, $content);
                        break;
                    case 'template':
                        file_put_contents($block_dir . '/block.twig', $content);
                        break;
                    case 'block-json':
                        $decoded = json_decode($content);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return new WP_Error(
                                'invalid_json',
                                'Invalid JSON provided for block.json',
                                ['status' => 400]
                            );
                        }
                        file_put_contents($block_dir . '/block.json', $content);
                        break;
                    default:
                        file_put_contents($block_dir . '/block.' . $field, $content);
                }
            }
        }

        return self::get_block($request);
    }
}
