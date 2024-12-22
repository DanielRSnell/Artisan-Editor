<?php
namespace ClientBlocks\Blocks\Registry;

use ClientBlocks\Blocks\BlockContext;
use ClientBlocks\Blocks\BlockPreview;
use Timber\Timber;

class BlockRenderer
{
    public static function render($block, $content, $is_preview, $post_id, $block_data, $block_name)
    {
        try {
            // Get base context
            $context = BlockContext::get_context();

            // Add block data to context
            $context['block'] = array_merge($block, [
                'name' => $block_name,
                'post_id' => $post_id,
                'is_preview' => $is_preview,
                'inner_blocks' => $content, // Add inner blocks content
            ]);

            // Add ACF fields
            $context['fields'] = get_fields();

            // Add block attributes
            $context['attributes'] = $block['data'] ?? [];

            if (isset($context['block']['data']['mock_fields'])) {
                $context['fields'] = $context['block']['data']['mock_fields'];
            } else {
                $context['fields'] = get_fields();
            }

            // Execute PHP code if exists
            if (!empty($block_data['php'])) {
                $php_context = self::execute_php($block_data['php'], $context = $context, $context['block']);
                if (is_array($php_context)) {
                    $context = array_merge($context, $php_context);
                }
            }

            // Prepare template with inline styles and scripts
            $template = self::prepare_template($block_data, $block);

            $template = do_shortcode($template);

            // Compile template
            $rendered_content = Timber::compile_string($template, $context);

            // Output the content
            if ($is_preview) {
                BlockPreview::render($rendered_content, $context['block']);
            } else {
                echo $rendered_content;
            }

        } catch (\Exception $e) {
            echo '<div class="notice notice-error"><p>Error rendering block: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    private static function execute_php($code, $block)
    {
        try {
            return eval('?>' . $code);
        } catch (\Throwable $e) {
            error_log('Block PHP execution error: ' . $e->getMessage());
            return ['error' => 'Error executing block PHP code'];
        }
    }

    private static function prepare_template($block_data, $block)
    {
        $template = $block_data['template'];

        // Add unique ID wrapper

        // Add CSS with scoped selector
        if (!empty($block_data['css'])) {
            $css = str_replace('.example-block', '#block-' . esc_attr($block['id']), $block_data['css']);
            $template = '<style>' . $css . '</style>' . $template;
        }

        // Add JS with block ID
        if (!empty($block_data['js'])) {
            $js = str_replace('{{ block.id }}', 'block-' . esc_attr($block['id']), $block_data['js']);
            $template .= '<script>' . $js . '</script>';
        }

        return $template;
    }
}
