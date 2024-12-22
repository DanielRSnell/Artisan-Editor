<?php
namespace ClientBlocks\Admin\Editor;

class BreakpointManager {
    private static $instance = null;
    private $upload_dir;
    private $breakpoints_file;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->breakpoints_file = $this->upload_dir['basedir'] . '/client-blocks/data/breakpoints.json';
        
        if (!file_exists(dirname($this->breakpoints_file))) {
            wp_mkdir_p(dirname($this->breakpoints_file));
        }
        
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    private function get_default_breakpoints() {
        return [
            [
                'id' => 'xs',
                'name' => 'X-Small',
                'width' => 375,
                'icon' => 'phone-portrait-outline'
            ],
            [
                'id' => 'sm',
                'name' => 'Small',
                'width' => 576,
                'icon' => 'phone-landscape-outline'
            ],
            [
                'id' => 'md',
                'name' => 'Medium',
                'width' => 768,
                'icon' => 'tablet-portrait-outline'
            ],
            [
                'id' => 'lg',
                'name' => 'Large',
                'width' => 992,
                'icon' => 'tablet-landscape-outline'
            ],
            [
                'id' => 'xl',
                'name' => 'Extra Large',
                'width' => 1200,
                'icon' => 'laptop-outline'
            ],
            [
                'id' => 'xxl',
                'name' => 'Extra Extra Large',
                'width' => 1400,
                'icon' => 'desktop-outline'
            ],
            [
                'id' => 'retina',
                'name' => 'Retina',
                'width' => 2560,
                'icon' => 'expand-outline'
            ]
        ];
    }
    
    private function save_default_breakpoints() {
        $default_breakpoints = $this->get_default_breakpoints();
        file_put_contents($this->breakpoints_file, json_encode($default_breakpoints, JSON_PRETTY_PRINT));
        return $default_breakpoints;
    }
    
    public function get_breakpoints() {
        if (!file_exists($this->breakpoints_file) || filesize($this->breakpoints_file) == 0) {
            return $this->save_default_breakpoints();
        }
        
        $breakpoints = json_decode(file_get_contents($this->breakpoints_file), true);
        if (empty($breakpoints) || !is_array($breakpoints)) {
            return $this->save_default_breakpoints();
        }
        
        return $breakpoints;
    }
    
    public function save_breakpoints($breakpoints) {
        file_put_contents($this->breakpoints_file, json_encode($breakpoints, JSON_PRETTY_PRINT));
    }
    
    public function register_rest_routes() {
        register_rest_route('client-blocks/v1', '/breakpoints', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_breakpoints_endpoint'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_breakpoints_endpoint'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                }
            ]
        ]);
    }
    
    public function get_breakpoints_endpoint() {
        return rest_ensure_response($this->get_breakpoints());
    }
    
    public function update_breakpoints_endpoint($request) {
        $breakpoints = $request->get_json_params();
        
        if (!is_array($breakpoints)) {
            return new \WP_Error('invalid_breakpoints', 'Invalid breakpoints data', ['status' => 400]);
        }
        
        $this->save_breakpoints($breakpoints);
        return rest_ensure_response($this->get_breakpoints());
    }
}
