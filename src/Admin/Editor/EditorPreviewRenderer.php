<?php
namespace ClientBlocks\Admin\Editor;

use Timber\Timber;

class EditorPreviewRenderer
{
    public static function render($data)
    {
        global $post;
        $context = Timber::context();
        $block_id = $data['block_id'];
        $block = get_post($block_id);

        if (!$block || $block->post_type !== 'client_blocks') {
            return new \WP_Error('invalid_block', 'Invalid block ID');
        }

        $post_context = json_decode($data['post_context'], true);
        $mock_fields = json_decode($data['mock_fields'], true);
        $block_context = json_decode($data['block_context'], true);

        $context = array_merge($post_context, ['fields' => $mock_fields]);

        $context['block'] = array_merge($block_context, [
            'id' => $block_id,
            'post' => $block,
        ]);

        $context['test'] = 'test';

        if (!empty($block_context['php'])) {
            $context = self::execute_php($block_context['php'], $context = $context, $context['block']);
        }

        $context = apply_filters('client_blocks_context', $context, $context['block']);

        if (!empty($block_context['css'])) {
            $processed_css = self::process_php_content($block_context['css'], $context, $context['block']);
            $template = '<style>' . $processed_css . '</style>' . $block_context['template'];
        } else {
            $template = $block_context['template'];
        }

        $processed_template = self::process_php_content($template, $context, $context['block']);

        if (!empty($block_context['js'])) {
            $processed_js = self::process_php_content($block_context['js'], $context, $context['block']);
            $processed_template .= '<script>' . $processed_js . '</script>';
        }

        $rendered_content = Timber::compile_string($processed_template, $context);

        return [
            'content' => $rendered_content,
            'context' => $context,
        ];
    }

    private static function execute_php($code, $context, $block)
    {
        extract($context);
        ob_start();
        $result = eval('?>' . $code);
        $output = ob_get_clean();

        if (is_array($result)) {
            $context = array_merge($context, $result);
        }

        if (!empty($output)) {
            $context['output'] = $output;
        }

        return $context;
    }

    private static function process_php_content($content, $context, $block)
    {
        ob_start();
        extract($context);
        eval('?>' . $content);
        $processed_content = ob_get_clean();
        return $processed_content;
    }
}
