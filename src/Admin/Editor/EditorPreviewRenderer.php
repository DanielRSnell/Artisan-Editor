<?php
namespace ClientBlocks\Admin\Editor;

use ClientBlocks\Renderer;

class EditorPreviewRenderer
{
    public static function render($data)
    {
        $block_id = $data['block_id'];
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new \WP_Error('invalid_block', 'Invalid block ID');
        }

        $post_context = json_decode($data['post_context'], true);
        $mock_fields = json_decode($data['mock_fields'], true);
        $block_context = json_decode($data['block_context'], true);

        $block_data = [
            'template_id' => $block_id,
            'php' => $block_context['php'] ?? '',
            'template' => $block_context['template'] ?? '',
            'js' => $block_context['js'] ?? '',
            'css' => $block_context['css'] ?? '',
        ];

        $block = array_merge($block_context, [
            'id' => $block_id,
            'post' => $block,
            'data' => [
                'mock_fields' => $mock_fields,
            ],
        ]);

        // Remove php/js/css/template from block data
        unset($block['php'], $block['js'], $block['css'], $block['template']);

        $context = Renderer::get_context($block, '', true, $block_id, $block_data);
        $context = array_merge($context, $post_context);

        ob_start();
        Renderer::render($block, '', true, $block_id, $block_data);
        $rendered_content = ob_get_clean();

        return [
            'content' => $rendered_content,
            'context' => $context,
        ];
    }
}
