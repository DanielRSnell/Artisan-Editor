<?php
namespace ClientBlocks\Blocks\Registry;

class BlockRegistrar
{
    private static $instance = null;
    private $blocks_dir;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->blocks_dir = $upload_dir['basedir'] . '/client-blocks/blocks';

        add_action('init', [$this, 'register_blocks']);
    }

    public function register_blocks()
    {
        $block_folders = glob($this->blocks_dir . '/*', GLOB_ONLYDIR);

        foreach ($block_folders as $folder) {
            register_block_type($folder);
        }
    }
}
