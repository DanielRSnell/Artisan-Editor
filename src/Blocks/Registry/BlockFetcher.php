<?php
namespace ClientBlocks\Blocks\Registry;

class BlockFetcher {
    public static function get_blocks() {
        return get_posts([
            'post_type' => 'client_blocks',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
    }
}
