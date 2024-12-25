<?php
namespace ClientBlocks\API;

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
    }

    public function check_permission() {
        return current_user_can('edit_posts');
    }
}
