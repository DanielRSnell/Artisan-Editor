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

        $required_fields = ['post_context', 'mock_fields', 'block_context'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new \WP_Error('missing_field', "Field {$field} is required", ['status' => 400]);
            }
        }

        $result = EditorPreviewRenderer::render($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'content' => $result['content'],
            'context' => $result['context'],
        ]);
    }
}
