<?php
namespace ClientBlocks\API;

use ClientBlocks\Admin\Editor\GlobalCSSManager;
use ClientBlocks\Admin\Editor\GlobalJSManager;

class RestController {
    private static $instance = null;
    private $namespace = 'client-blocks/v1';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Existing routes
        register_rest_route($this->namespace, '/blocks/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [BlockEndpoints::class, 'get_block'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [BlockEndpoints::class, 'update_block'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/blocks/(?P<id>\d+)/global-save', [
            [
                'methods' => 'POST',
                'callback' => [BlockEndpoints::class, 'global_save_block'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Add preview endpoint
        register_rest_route($this->namespace, '/preview', [
            [
                'methods' => 'POST',
                'callback' => [PreviewEndpoint::class, 'render_preview'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'block_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'template' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'php' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'css' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'js' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'json' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/global-css', [
            [
                'methods' => 'GET',
                'callback' => [GlobalCSSManager::class, 'get_css_endpoint'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [GlobalCSSManager::class, 'update_css_endpoint'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/global-js', [
            [
                'methods' => 'GET',
                'callback' => [GlobalJSManager::class, 'get_js_endpoint'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [GlobalJSManager::class, 'update_js_endpoint'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);
    }

    public function check_permission() {
        return current_user_can('edit_posts');
    }
}
