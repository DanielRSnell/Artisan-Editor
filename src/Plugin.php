<?php
namespace ClientBlocks;

use ClientBlocks\Admin\Editor\EditorPage;
use ClientBlocks\API\RestController;
use ClientBlocks\Blocks\Registry\BlockRegistrar;
use ClientBlocks\PostType\BlockPostType;
use Timber\Timber;

class Plugin
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'init_timber']);
        add_filter('timber/locations', [$this, 'add_timber_locations']);

        $this->init_modules();
    }

    public function init_timber()
    {
        if (class_exists('Timber\Timber')) {
            Timber::init();
        }
    }

    public function add_timber_locations($paths)
    {
        $upload_dir = wp_upload_dir();
        $blocks_dir = $upload_dir['basedir'] . '/client-blocks/blocks';

        $paths['client_blocks'] = [CLIENT_BLOCKS_PATH . 'views'];
        $paths['block'] = [$blocks_dir];
        return $paths;
    }

    private function init_modules()
    {
        BlockPostType::instance();
        BlockRegistrar::instance();
        EditorPage::instance();
        RestController::instance();
    }
}
