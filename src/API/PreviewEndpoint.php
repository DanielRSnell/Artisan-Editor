<?php
namespace ClientBlocks\API;

use ClientBlocks\Admin\Editor\EditorPreviewRenderer;
use WP_REST_Request;

class PreviewEndpoint
{
    public static function render_preview(WP_REST_Request $request)
    {
        $data = $request->get_params();

        if (!isset($data['block_id'])) {
            return new \WP_Error('missing_block_id', 'Block ID is required', ['status' => 400]);
        }

        $block = get_post($data['block_id']);
        if (!$block || $block->post_type !== 'client_blocks') {
            return new \WP_Error('invalid_block', 'Invalid block ID', ['status' => 400]);
        }

        // Create block data structure
        $block_data = [
            'id' => $block->ID,
            'name' => 'acf/' . $block->post_name,
            'data' => [
                'template' => $data['template'] ?? '',
                'php' => $data['php'] ?? '',
                'css' => $data['css'] ?? '',
                'js' => $data['js'] ?? '',
                'block_json' => $data['json'] ?? '{}',
            ],
            'is_preview' => true,
            'align' => $data['align'] ?? '',
            'className' => $data['className'] ?? '',
            'mode' => $data['mode'] ?? 'preview',
            'supports' => $data['supports'] ?? [],
        ];

        try {
            $result = EditorPreviewRenderer::render($block_data);

            // Only return the JSON response
            return rest_ensure_response([
                'content' => $result['content'],
                'context' => $result['context'],
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'preview_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
