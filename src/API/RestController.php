<?php
namespace ClientBlocks\API;

use WP_REST_Request;

class RestController
{
    private static $instance = null;
    private $namespace = 'client-blocks/v1';

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/blocks', [
            [
                'methods' => 'GET',
                'callback' => [BlockEndpoints::class, 'get_blocks'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route($this->namespace, '/blocks/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [BlockEndpoints::class, 'get_block'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [BlockEndpoints::class, 'update_block'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_block_args(),
            ],
        ]);

        register_rest_route($this->namespace, '/blocks/(?P<id>\d+)/global-save', [
            [
                'methods' => 'POST',
                'callback' => [BlockEndpoints::class, 'global_save_block'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_global_save_args(),
            ],
        ]);

        register_rest_route($this->namespace, '/categories', [
            [
                'methods' => 'GET',
                'callback' => [CategoryEndpoints::class, 'get_categories'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route($this->namespace, '/preview', [
            [
                'methods' => 'POST',
                'callback' => [PreviewEndpoint::class, 'render_preview'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_preview_args(),
            ],
        ]);
    }

    public function check_permission()
    {
        return current_user_can('edit_posts');
    }

    private function get_block_args()
    {
        return [
            'client_php' => [
                'type' => 'string',
                'required' => false,
            ],
            'client_template' => [
                'type' => 'string',
                'required' => false,
            ],
            'client_js' => [
                'type' => 'string',
                'required' => false,
            ],
            'client_css' => [
                'type' => 'string',
                'required' => false,
            ],
        ];
    }

    private function get_global_save_args()
    {
        return [
            'php' => [
                'type' => 'string',
                'required' => true,
            ],
            'template' => [
                'type' => 'string',
                'required' => true,
            ],
            'css' => [
                'type' => 'string',
                'required' => true,
            ],
            'js' => [
                'type' => 'string',
                'required' => true,
            ],
            'global-css' => [
                'type' => 'string',
                'required' => true,
            ],
        ];
    }

    private function get_preview_args()
    {
        return [
            'block_id' => [
                'type' => 'integer',
                'required' => true,
            ],
            'post_context' => [
                'type' => 'string',
                'required' => true,
            ],
            'mock_fields' => [
                'type' => 'string',
                'required' => true,
            ],
            'block_context' => [
                'type' => 'string',
                'required' => true,
            ],
        ];
    }
}
