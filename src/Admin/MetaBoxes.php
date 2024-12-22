<?php
namespace ClientBlocks\Admin;

use ClientBlocks\Blocks\BlockDefaults;
use ClientBlocks\Utils\Security;

class MetaBoxes
{
    private static $instance = null;
    private $fields = ['client_php', 'client_template', 'client_js', 'client_css'];

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register']);
        add_action('save_post_client_blocks', [$this, 'save']);
        add_action('load-post-new.php', [$this, 'maybe_set_defaults']);

        // Prevent sanitization for specific fields
        add_filter('sanitize_meta__client_php', '__return_null');
        add_filter('sanitize_meta__client_js', '__return_null');
    }

    public function register()
    {
        add_meta_box(
            'client_block_fields',
            'Block Content',
            [$this, 'render'],
            'client_blocks',
            'normal',
            'high'
        );
    }

    public function render($post)
    {
        Security::verify_nonce('client_blocks_meta_box');

        $values = [];
        foreach ($this->fields as $field) {
            $values[$field] = get_post_meta($post->ID, '_' . $field, true);
        }

        \Timber::render('admin/meta-boxes.twig', [
            'fields' => $this->fields,
            'values' => $values,
        ]);
    }

    public function save($post_id)
    {
        if (!Security::verify_save_post($post_id, 'client_blocks_meta_box')) {
            return;
        }

        foreach ($this->fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];

                // Skip sanitization for specific fields
                if (in_array($field, ['client_php', 'client_js'], true)) {
                    // Save raw content without sanitization
                    update_post_meta($post_id, '_' . $field, $value);
                } else {
                    // Apply sanitization for other fields
                    update_post_meta($post_id, '_' . $field, wp_kses_post($value));
                }
            }
        }
    }

    public function maybe_set_defaults()
    {
        global $typenow;

        if ($typenow !== 'client_blocks') {
            return;
        }

        add_filter('default_post_metadata', function ($value, $post_id, $meta_key, $single) {
            if (!$single) {
                return $value;
            }

            switch ($meta_key) {
                case '_client_php':
                    return BlockDefaults::get_default_php();
                case '_client_template':
                    return BlockDefaults::get_default_template();
                case '_client_js':
                    return BlockDefaults::get_default_js();
                case '_client_css':
                    return BlockDefaults::get_default_css();
                default:
                    return $value;
            }
        }, 10, 4);
    }
}

// Add column to list view
add_filter('manage_client_blocks_posts_columns', function ($columns) {
    $columns['artisan_editor'] = 'Artisan Editor';
    return $columns;
});

// Populate the column content
add_action('manage_client_blocks_posts_custom_column', function ($column_name, $post_id) {
    if ($column_name !== 'artisan_editor') {
        return;
    }

    $permalink = get_permalink($post_id);
    $editor_url = $permalink . '?block_id=' . $post_id . '&artisan=editor';

    printf(
        '<a href="%s" class="button button-secondary">Open in Artisan</a>',
        esc_url($editor_url)
    );
}, 10, 2);

// Add editor button area to individual post page
add_action('edit_form_after_title', function ($post) {
    if ($post->post_type !== 'client_blocks') {
        return;
    }

    $permalink = get_permalink($post->ID);
    $editor_url = $permalink . '?block_id=' . $post->ID . '&artisan=editor';
    $plugin_url = plugin_dir_url(__DIR__) . '../assets/images/artisan-backdrop.png';

    ?>
    <div class="client_block_fields">
        <style>
            .artisan-editor-container {
                width: 100%;
                height: 500px;
                margin: 20px auto;
                position: relative;
                border-radius: 4px;
                overflow: hidden;
                background-image: url('<?php echo esc_url($plugin_url); ?>');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }

            .artisan-editor-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.3s ease;
            }

            .artisan-editor-container:hover .artisan-editor-overlay {
                background: rgba(0, 0, 0, 0.5);
            }

            .artisan-editor-button {
                display: inline-block;
                padding: 15px 25px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                background: #2271b1;
                color: #fff;
                border-radius: 3px;
                transition: background-color 0.2s ease;
                z-index: 2;
            }

            #postbox-container-2 > #normal-sortables >#client_block_fields {
                display: none!important;
            }

            .artisan-editor-button:hover {
                background: #135e96;
                color: #fff;
            }
        </style>

        <div class="artisan-editor-container">
            <div class="artisan-editor-overlay">
                <a href="<?php echo esc_url($editor_url); ?>" class="artisan-editor-button">
                    Open in Artisan Editor
                </a>
            </div>
        </div>
    </div>
    <?php
});

/* Screen Modes for Admin */
add_action('admin_head', function () {
    // Check if we're on a Gutenberg editor page
    $screen = get_current_screen();
    if ($screen && $screen->is_block_editor()) {
        return;
    }

    // Define the fullscreen styles
    $fullscreen_styles = '
        html {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        #wpcontent,
        #wpfooter {
            margin-left: 0 !important;
        }
        #wpadminbar {
            display: none !important;
        }
        #adminmenuwrap,
        #adminmenuback {
            display: none !important;
        }
    ';

    // Output styles if screen=full is present
    if (isset($_GET['screen']) && $_GET['screen'] === 'full') {
        echo '<style id="wp-admin-fullscreen">' . $fullscreen_styles . '</style>';
    }

    // Add JavaScript to check window.parent.screen
    ?>
    <script>
        (function() {
            // Create a style element for dynamic styles
            const styleElement = document.createElement('style');
            styleElement.id = 'wp-admin-fullscreen-dynamic';
            document.head.appendChild(styleElement);

            function checkParentScreen() {
                // Check if we're in an iframe and have a parent window
                if (window.parent !== window && window.parent.screen) {
                    styleElement.textContent = <?php echo json_encode($fullscreen_styles); ?>;
                } else {
                    styleElement.textContent = '';
                }
            }

            // Check immediately
            checkParentScreen();

            // Also check on load in case of dynamic changes
            window.addEventListener('load', checkParentScreen);
        })();
    </script>
    <?php
});

// ACF JSON Custom Save Point
add_action('after_setup_theme', function () {
    // Debug: Log when files are being scanned
    add_filter('acf/json/load_paths', function ($paths) {
        error_log('ACF JSON Paths being scanned: ' . print_r($paths, true));
        return $paths;
    });

    // Debug: Log file contents when loaded
    add_filter('acf/json/load_file_contents', function ($content, $file) {
        error_log('ACF trying to load file: ' . $file);
        if ($content === null) {
            error_log('File content was null for: ' . $file);
        }
        return $content;
    }, 10, 2);

    // Original load/save point code
    add_filter('acf/settings/load_json', function ($paths) {
        unset($paths[0]);
        $UPLOADS_DIR = wp_upload_dir();
        $paths[] = $UPLOADS_DIR['basedir'] . '/client-blocks/scf/fields';
        return $paths;
    });

    add_filter('acf/settings/save_json', function ($path) {
        $UPLOADS_DIR = wp_upload_dir();
        return $UPLOADS_DIR['basedir'] . '/client-blocks/scf/fields';
    });
});
