<?php
namespace ClientBlocks\Blocks\Support;

class BlockSupports {
    public static function get_supports($block) {
        $supports = [
            'mode' => 'auto', // Always enable mode switching
            'multiple' => true, // Always allow multiple instances
            'jsx' => false, // Default to false unless explicitly enabled
        ];
        
        // Add optional supports based on block settings
        $supports = self::add_inner_blocks_support($supports, $block);
        $supports = self::add_alignment_supports($supports, $block);
        
        return $supports;
    }
    
    private static function add_inner_blocks_support($supports, $block) {
        if (get_post_meta($block->ID, '_supports_inner_blocks', true) === '1') {
            $supports['jsx'] = true;
        }
        return $supports;
    }
    
    private static function add_alignment_supports($supports, $block) {
        // Block alignment (wide/full)
        if (get_post_meta($block->ID, '_supports_align', true) === '1') {
            $supports['align'] = ['wide', 'full'];
        }
        
        // Text alignment
        if (get_post_meta($block->ID, '_supports_align_text', true) === '1') {
            $supports['align_text'] = true;
        }
        
        // Content alignment
        if (get_post_meta($block->ID, '_supports_align_content', true) === '1') {
            $supports['align_content'] = true;
        }
        
        return $supports;
    }
}
