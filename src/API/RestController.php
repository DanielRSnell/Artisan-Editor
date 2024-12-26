<?php
namespace ClientBlocks\API;

use ClientBlocks\Admin\Editor\GlobalFilesManager;

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
        // Block endpoints
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

        register_rest_route($this->namespace, '/preview', [
            [
                'methods' => 'POST',
                'callback' => [PreviewEndpoint::class, 'render_preview'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/preview-contexts', [
            [
                'methods' => 'GET',
                'callback' => [PreviewContextEndpoint::class, 'get_preview_contexts'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Global Files endpoints
        register_rest_route($this->namespace, '/global-files/(?P<type>css|js)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_global_files'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'type' => [
                        'required' => true,
                        'enum' => ['css', 'js']
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_global_files'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'type' => [
                        'required' => true,
                        'enum' => ['css', 'js']
                    ]
                ]
            ]
        ]);

        register_rest_route($this->namespace, '/global-files/(?P<type>css|js)/create', [
            'methods' => 'POST',
            'callback' => [$this, 'create_global_file'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'type' => [
                    'required' => true,
                    'enum' => ['css', 'js']
                ],
                'filename' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);
    }

    public function check_permission() {
        return current_user_can('edit_posts');
    }

    public function get_global_files($request) {
        return GlobalFilesManager::instance()->get_files_endpoint($request);
    }

    public function save_global_files($request) {
        return GlobalFilesManager::instance()->save_files_endpoint($request);
    }

    public function create_global_file($request) {
        return GlobalFilesManager::instance()->create_file_endpoint($request);
    }
}
