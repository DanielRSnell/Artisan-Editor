<?php
namespace ClientBlocks\Admin\ACF;

class FieldManager {
    private static $instance = null;
    private $upload_dir;
    private $base_dir;
    private $paths = [];

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->base_dir = $this->upload_dir['basedir'] . '/client-blocks/structure';
        
        // Define paths for actual ACF types
        $this->paths = [
            'base' => $this->base_dir,
            'field-groups' => $this->base_dir . '/field-groups',
            'post-types' => $this->base_dir . '/post-types',
            'taxonomies' => $this->base_dir . '/taxonomies',
            'options' => $this->base_dir . '/options'
        ];

        add_action('after_setup_theme', [$this, 'setup_acf_json']);
        add_action('acf/init', [$this, 'init_acf']);
    }

    public function setup_acf_json() {
        // Base save point filter defaults to field-groups
        add_filter('acf/settings/save_json', function() {
            return $this->paths['field-groups'];
        });

        // Type-specific save points
        add_filter('acf/settings/save_json/type=acf-field-group', function() {
            return $this->paths['field-groups'];
        });

        add_filter('acf/settings/save_json/type=acf-post-type', function() {
            return $this->paths['post-types'];
        });

        add_filter('acf/settings/save_json/type=acf-taxonomy', function() {
            return $this->paths['taxonomies'];
        });

        add_filter('acf/settings/save_json/type=acf-ui-options-page', function() {
            return $this->paths['options'];
        });

        // Load points
        add_filter('acf/settings/load_json', [$this, 'add_load_points']);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_filter('acf/json/load_paths', [$this, 'log_load_paths']);
            add_filter('acf/json/load_file_contents', [$this, 'log_file_contents'], 10, 2);
        }
    }

    public function init_acf() {
        $this->ensure_directories_exist();
        $this->create_gitkeep_files();
    }

    private function ensure_directories_exist() {
        foreach ($this->paths as $path) {
            if (!file_exists($path)) {
                wp_mkdir_p($path);
                $this->debug_log("Created directory: $path");
            }
        }
    }

    private function create_gitkeep_files() {
        foreach ($this->paths as $path) {
            $gitkeep = $path . '/.gitkeep';
            if (!file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
                $this->debug_log("Created .gitkeep in: $path");
            }
        }
    }

    public function add_load_points($paths) {
        if (isset($paths[0])) {
            unset($paths[0]);
        }

        foreach ($this->paths as $path) {
            if (file_exists($path)) {
                $paths[] = $path;
            }
        }

        $this->debug_log('Load paths:', $paths);
        return $paths;
    }

    public function log_load_paths($paths) {
        $this->debug_log('Structure JSON Paths being scanned:', $paths);
        return $paths;
    }

    public function log_file_contents($content, $file) {
        $this->debug_log("Loading field file: $file");
        if ($content === null) {
            $this->debug_log("Warning: Null content for file: $file");
        }
        return $content;
    }

    private function debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($data !== null) {
                error_log('FieldManager: ' . $message . ' ' . print_r($data, true));
            } else {
                error_log('FieldManager: ' . $message);
            }
        }
    }

    public function get_path($key) {
        return isset($this->paths[$key]) ? $this->paths[$key] : $this->paths['base'];
    }

    public function get_paths() {
        return $this->paths;
    }

    /**
     * Add a custom save point for a specific field group name
     */
    public function add_save_point_by_name($name, $path) {
        add_filter("acf/settings/save_json/name={$name}", function() use ($path) {
            return $path;
        });
    }
}
