<?php
namespace ClientBlocks\Admin\Editor;

class GlobalFilesManager {
    private static $instance = null;
    private $upload_dir;
    private $css_dir;
    private $js_dir;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->css_dir = $this->upload_dir['basedir'] . '/client-blocks/global/css';
        $this->js_dir = $this->upload_dir['basedir'] . '/client-blocks/global/js';
        
        $this->ensure_directories();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    private function ensure_directories() {
        if (!file_exists($this->css_dir)) {
            wp_mkdir_p($this->css_dir);
            $this->create_default_css();
        } elseif (!file_exists($this->css_dir . '/main.css')) {
            $this->create_default_css();
        }
        
        if (!file_exists($this->js_dir)) {
            wp_mkdir_p($this->js_dir);
            $this->create_default_js();
        } elseif (!file_exists($this->js_dir . '/main.js')) {
            $this->create_default_js();
        }
    }

    private function create_default_css() {
        $default_css = <<<CSS
/* Main Global CSS */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
}

.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.flex { display: flex; }
.flex-col { flex-direction: column; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.justify-between { justify-content: space-between; }
.flex-wrap { flex-wrap: wrap; }

.grid { display: grid; }
.gap-4 { gap: 1rem; }
.gap-8 { gap: 2rem; }
CSS;
        file_put_contents($this->css_dir . '/main.css', $default_css);
    }

    private function create_default_js() {
        $default_js = <<<JS
// Main Global JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeClientBlocks();
});

function initializeClientBlocks() {
    const blocks = document.querySelectorAll('.wp-block-acf');
    blocks.forEach(block => {
        setupBlockEvents(block);
    });
}

function setupBlockEvents(block) {
    block.addEventListener('click', function(e) {
        const clickEvent = new CustomEvent('block-clicked', {
            detail: { blockId: block.id }
        });
        document.dispatchEvent(clickEvent);
    });
}

const ClientBlocksUtils = {
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle: (func, limit) => {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};
JS;
        file_put_contents($this->js_dir . '/main.js', $default_js);
    }

    public function get_files($type) {
        $dir = $type === 'css' ? $this->css_dir : $this->js_dir;
        $files = [];
        
        if (!is_dir($dir)) {
            return $files;
        }

        $file_pattern = $dir . '/*.' . $type;
        $file_paths = glob($file_pattern);

        if (!empty($file_paths)) {
            foreach ($file_paths as $file_path) {
                $files[] = [
                    'name' => basename($file_path),
                    'path' => str_replace($this->upload_dir['basedir'], '', $file_path),
                    'full_path' => $file_path,
                    'content' => file_get_contents($file_path),
                    'modified' => filemtime($file_path)
                ];
            }

            usort($files, function($a, $b) {
                if ($a['name'] === 'main.css' || $a['name'] === 'main.js') return -1;
                if ($b['name'] === 'main.css' || $b['name'] === 'main.js') return 1;
                return $b['modified'] - $a['modified'];
            });
        }

        return $files;
    }

    public function save_files($type, $files) {
        $dir = $type === 'css' ? $this->css_dir : $this->js_dir;
        $success = true;
        $errors = [];

        foreach ($files as $file) {
            if (!isset($file['name']) || !isset($file['content'])) {
                $errors[] = 'Invalid file data structure';
                continue;
            }

            $filepath = $dir . '/' . sanitize_file_name($file['name']);
            
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }

            $result = file_put_contents($filepath, $file['content']);
            if ($result === false) {
                $success = false;
                $errors[] = "Failed to save {$file['name']}";
            }
        }

        if (!$success) {
            throw new \Exception('Failed to save files: ' . implode(', ', $errors));
        }

        return true;
    }

    public function create_file($type, $filename) {
        $dir = $type === 'css' ? $this->css_dir : $this->js_dir;
        
        if (!str_ends_with($filename, '.' . $type)) {
            $filename .= '.' . $type;
        }

        $filepath = $dir . '/' . sanitize_file_name($filename);
        
        if (file_exists($filepath)) {
            return false;
        }

        $initial_content = $type === 'css' 
            ? "/* {$filename} - Created " . date('Y-m-d H:i:s') . " */"
            : "// {$filename} - Created " . date('Y-m-d H:i:s');

        file_put_contents($filepath, $initial_content);
        return true;
    }

    public function register_rest_routes() {
        register_rest_route('client-blocks/v1', '/global-files/(?P<type>css|js)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_files_endpoint'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                'args' => [
                    'type' => [
                        'required' => true,
                        'enum' => ['css', 'js']
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_files_endpoint'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                'args' => [
                    'type' => [
                        'required' => true,
                        'enum' => ['css', 'js']
                    ]
                ]
            ]
        ]);

        register_rest_route('client-blocks/v1', '/global-files/(?P<type>css|js)/create', [
            'methods' => 'POST',
            'callback' => [$this, 'create_file_endpoint'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
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

    public function get_files_endpoint($request) {
        $type = $request['type'];
        $files = $this->get_files($type);
        
        return rest_ensure_response([
            'files' => $files,
            'directory' => $type === 'css' ? $this->css_dir : $this->js_dir,
            'base_url' => $this->upload_dir['baseurl'] . '/client-blocks/global/' . $type
        ]);
    }

    public function create_file_endpoint($request) {
        $created = $this->create_file(
            $request['type'],
            $request['filename']
        );

        if (!$created) {
            return new \WP_Error(
                'file_exists',
                'File already exists',
                ['status' => 400]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'files' => $this->get_files($request['type'])
        ]);
    }

    public function save_files_endpoint($request) {
        try {
            $type = $request['type'];
            $files = $request->get_json_params();
            
            if (!is_array($files)) {
                return new \WP_Error(
                    'invalid_data',
                    'Invalid files data provided',
                    ['status' => 400]
                );
            }

            $this->save_files($type, $files);

            return rest_ensure_response([
                'success' => true,
                'files' => $this->get_files($type)
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'save_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
