<?php
namespace ClientBlocks\Admin\Editor;

use Timber\Timber;

class EditorPreviewRenderer
{
    public static function render($block_data)
    {
        try {
            // Start output buffering to catch any unexpected output
            ob_start();

            // Set up the context
            $context = Timber::context();

            // Mimic Gutenberg block structure
            $block = [
                'id' => $block_data['id'],
                'name' => $block_data['name'],
                'data' => $block_data['data'],
                'align' => $block_data['align'] ?? '',
                'mode' => 'preview',
                'supports' => self::get_block_supports($block_data),
                'className' => $block_data['className'] ?? '',
                'anchor' => '',
                'is_preview' => true,
            ];

            // Add block to context
            $context['block'] = $block;

            // Execute PHP logic if present
            if (!empty($block_data['data']['php'])) {
                try {
                    ob_start();
                    $php_result = eval('?>' . $block_data['data']['php']);
                    ob_end_clean(); // Discard any output from PHP
                    if (is_array($php_result)) {
                        $context = array_merge($context, $php_result);
                    }
                } catch (\ParseError $e) {
                    $context['php_error'] = $e->getMessage();
                }
            }

            // if $context['fields'] is false: $context['fields'] = $context['block']['id'];
            if ($context['fields'] === false) {
                $context['fields'] = get_fields($context['block']['id']);
            }

            // Compile the template using a clean buffer
            ob_start();
            $content = Timber::compile_string($block_data['data']['template'], $context);
            ob_end_clean(); // Discard any output from Timber

            // Wrap content with Gutenberg-style markup
            $wrapped_content = self::wrap_with_gutenberg_markup($content, $block_data, $context);

            // Clean any previous output
            ob_end_clean();

            // Return only the structured data
            return [
                'content' => $wrapped_content,
                'context' => $context,
                'mock_fields_id' => $context['block']['id'],
            ];
        } catch (\Exception $e) {
            ob_end_clean();
            throw new \Exception('Error rendering preview: ' . $e->getMessage());
        }
    }

    private static function wrap_with_gutenberg_markup($content, $block_data, $context)
    {
        $block_id = $block_data['id'];
        $block_name = str_replace('acf/', '', $block_data['name']);

        // Parse block JSON for additional settings
        $block_json = json_decode($block_data['data']['block_json'] ?? '{}', true);
        $block_settings = $block_json ?? [];

        // Build classes array similar to Gutenberg
        $classes = [
            'wp-block-acf-' . $block_name,
            'acf-block',
            'is-preview',
            !empty($block_settings['align']) ? 'align' . $block_settings['align'] : '',
            !empty($block_settings['className']) ? $block_settings['className'] : '',
        ];

        // Filter out empty classes
        $classes = array_filter($classes);

        // Build the wrapped content
        $output = [];

        // Opening tag
        $output[] = sprintf(
            '<div id="block-%s" class="%s" data-block="%s" data-name="%s" data-preview="true">',
            esc_attr($block_id),
            esc_attr(implode(' ', $classes)),
            esc_attr($block_id),
            esc_attr($block_name)
        );

        // Add block styles
        if (!empty($block_data['data']['css'])) {
            $output[] = sprintf(
                '<style>
                    /* Block-specific styles */
                    #block-%s {
                        %s
                    }
                </style>',
                esc_attr($block_id),
                $block_data['data']['css']
            );
        }

        // Add content
        $output[] = $content;

        // Add block scripts
        if (!empty($block_data['data']['js'])) {
            $output[] = sprintf(
                '<script>
                    (function() {
                        const block = document.getElementById("block-%s");
                        if (!block) return;
                        %s
                    })();
                </script>',
                esc_attr($block_id),
                $block_data['data']['js']
            );
        }

        $output[] = '</div>';

        // Return joined output
        return implode("\n", $output);
    }

    private static function get_block_supports($block_data)
    {
        $block_json = json_decode($block_data['data']['block_json'] ?? '{}', true);

        return array_merge([
            'align' => true,
            'mode' => true,
            'multiple' => true,
            'jsx' => true,
        ], $block_json['supports'] ?? []);
    }
}
