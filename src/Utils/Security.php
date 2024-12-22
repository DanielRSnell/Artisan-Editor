<?php
namespace ClientBlocks\Utils;

class Security {
    public static function verify_nonce($action) {
        wp_nonce_field($action, $action . '_nonce');
    }
    
    public static function verify_save_post($post_id, $action) {
        if (!isset($_POST[$action . '_nonce'])) {
            return false;
        }
        
        if (!wp_verify_nonce($_POST[$action . '_nonce'], $action)) {
            return false;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }
        
        return true;
    }
}
