<?php
namespace ClientBlocks\Admin\Screen;

class ScreenController {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_head', [$this, 'handle_screen_modes']);
    }

    public function handle_screen_modes() {
        // Skip for Gutenberg editor pages
        $screen = get_current_screen();
        if ($screen && $screen->is_block_editor()) {
            return;
        }

        $this->output_fullscreen_styles();
        $this->output_screen_check_script();
    }

    private function output_fullscreen_styles() {
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

        if (isset($_GET['screen']) && $_GET['screen'] === 'full') {
            echo '<style id="wp-admin-fullscreen">' . $fullscreen_styles . '</style>';
        }
    }

    private function output_screen_check_script() {
        ?>
        <script>
            (function() {
                const styleElement = document.createElement('style');
                styleElement.id = 'wp-admin-fullscreen-dynamic';
                document.head.appendChild(styleElement);

                function checkParentScreen() {
                    if (window.parent !== window && window.parent.screen) {
                        styleElement.textContent = <?php echo json_encode($this->get_fullscreen_styles()); ?>;
                    } else {
                        styleElement.textContent = '';
                    }
                }

                checkParentScreen();
                window.addEventListener('load', checkParentScreen);
            })();
        </script>
        <?php
    }

    private function get_fullscreen_styles() {
        return '
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
    }
}
