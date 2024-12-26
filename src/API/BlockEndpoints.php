<?php
namespace ClientBlocks\API;

use WP_Error;
use WP_REST_Request;
use ClientBlocks\Admin\Editor\GlobalFilesManager;

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

        wp_mkdir_p($block_dir);

        $files_manager = GlobalFilesManager::instance();
        $global_css_files = $files_manager->get_files('css');
        $global_js_files = $files_manager->get_files('js');

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
            'global_files' => [
                'css' => $global_css_files,
                'js' => $global_js_files
            ]
        ];
    }

    public static function update_block(WP_REST_Request $request) {
        try {
            $block_id = $request->get_param('id');
            $block = get_post($block_id);

            if (!$block || $block->post_type !== 'client_blocks') {
                return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
            }

            $data = $request->get_json_params();
            $slug = $block->post_name;
            $upload_dir = wp_upload_dir();
            $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;

            wp_mkdir_p($block_dir);

            $file_mappings = [
                'template' => 'block.twig',
                'php' => 'block.php',
                'js' => 'block.js',
                'css' => 'block.css',
                'block-json' => 'block.json'
            ];

            foreach ($file_mappings as $key => $filename) {
                if (isset($data[$key])) {
                    if ($key === 'block-json') {
                        $decoded = json_decode($data[$key]);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return new WP_Error(
                                'invalid_json',
                                'Invalid JSON provided for block.json',
                                ['status' => 400]
                            );
                        }
                    }
                    file_put_contents($block_dir . '/' . $filename, $data[$key]);
                }
            }

            if (isset($data['global_files'])) {
                $files_manager = GlobalFilesManager::instance();
                
                if (!empty($data['global_files']['css'])) {
                    $files_manager->save_files('css', $data['global_files']['css']);
                }
                
                if (!empty($data['global_files']['js'])) {
                    $files_manager->save_files('js', $data['global_files']['js']);
                }
            }

            return self::get_block($request);
        } catch (\Exception $e) {
            return new WP_Error(
                'save_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public static function global_save_block(WP_REST_Request $request) {
        try {
            $block_id = $request->get_param('id');
            $block = get_post($block_id);

            if (!$block || $block->post_type !== 'client_blocks') {
                return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
            }

            $data = $request->get_json_params();
            $slug = $block->post_name;
            $upload_dir = wp_upload_dir();
            $block_dir = $upload_dir['basedir'] . '/client-blocks/blocks/' . $slug;

            wp_mkdir_p($block_dir);

            $file_mappings = [
                'template' => 'block.twig',
                'php' => 'block.php',
                'js' => 'block.js',
                'css' => 'block.css',
                'block-json' => 'block.json'
            ];

            foreach ($file_mappings as $key => $filename) {
                if (isset($data[$key])) {
                    if ($key === 'block-json') {
                        $decoded = json_decode($data[$key]);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return new WP_Error(
                                'invalid_json',
                                'Invalid JSON provided for block.json',
                                ['status' => 400]
                            );
                        }
                    }
                    file_put_contents($block_dir . '/' . $filename, $data[$key]);
                }
            }

            if (isset($data['global_files'])) {
                $files_manager = GlobalFilesManager::instance();
                
                if (!empty($data['global_files']['css'])) {
                    $files_manager->save_files('css', $data['global_files']['css']);
                }
                
                if (!empty($data['global_files']['js'])) {
                    $files_manager->save_files('js', $data['global_files']['js']);
                }
            }

            return self::get_block($request);
        } catch (\Exception $e) {
            return new WP_Error(
                'save_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
