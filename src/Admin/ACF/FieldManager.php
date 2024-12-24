<?php
namespace ClientBlocks\Admin\ACF;

class FieldManager {
    private static $instance = null;
    private $base_dir;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->base_dir = wp_upload_dir()['basedir'] . '/client-blocks/structure';
        add_action('acf/init', [$this, 'init']);
    }

    public function init() {
        $this->create_directories();

        add_filter('acf/settings/save_json', [$this, 'set_base_save_path']);
        add_filter('acf/settings/save_json/type=acf-field-group', [$this, 'set_field_group_path']);
        add_filter('acf/settings/save_json/type=acf-post-type', [$this, 'set_post_type_path']);
        add_filter('acf/settings/save_json/type=acf-taxonomy', [$this, 'set_taxonomy_path']);
        add_filter('acf/settings/save_json/type=acf-ui-options-page', [$this, 'set_options_path']);
        add_filter('acf/json/save_file_name', [$this, 'set_filename_format'], 10, 3);
        add_filter('acf/settings/load_json', [$this, 'add_load_paths']);
    }

    private function create_directories() {
        $directories = [
            $this->base_dir . '/groups',
            $this->base_dir . '/post-types',
            $this->base_dir . '/taxonomies',
            $this->base_dir . '/options'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    public function set_base_save_path() {
        return $this->base_dir . '/groups';
    }

    public function set_field_group_path() {
        return $this->base_dir . '/groups';
    }

    public function set_post_type_path() {
        return $this->base_dir . '/post-types';
    }

    public function set_taxonomy_path() {
        return $this->base_dir . '/taxonomies';
    }

    public function set_options_path() {
        return $this->base_dir . '/options';
    }

    public function set_filename_format($filename, $post, $load_path) {
        if ($load_path) {
            return $filename;
        }

        $title = $post['title'] ?? '';
        if (empty($title)) {
            return $filename;
        }

        $filename = str_replace([' ', '_'], ['-', '-'], $title);
        $type = $post['type'] ?? '';

        switch ($type) {
            case 'acf-post-type':
                $filename = 'cpt-' . strtolower($filename);
                break;
            case 'acf-taxonomy':
                $filename = 'tax-' . strtolower($filename);
                break;
            case 'acf-ui-options-page':
                $filename = 'opt-' . strtolower($filename);
                break;
            default:
                $filename = strtolower($filename);
        }

        return $filename . '.json';
    }

    public function add_load_paths($paths) {
        if (isset($paths[0])) {
            unset($paths[0]);
        }

        $load_paths = [
            $this->base_dir . '/groups',
            $this->base_dir . '/post-types',
            $this->base_dir . '/taxonomies',
            $this->base_dir . '/options'
        ];

        return array_filter(array_merge($paths, $load_paths), 'file_exists');
    }
}
