// Iframe function with block refresh on close
// Parent page script
(function() {
    window.openClientBlocksEditor = function(block) {
        console.log('Opening Client Blocks Editor for block:', block);

        const { id, permalink } = block;
        const editorUrl = `${permalink}?block_id=${id}&artisan=editor&frame=1`;
        
        const iframe = document.createElement('iframe');
        iframe.src = editorUrl;
        iframe.style.position = 'fixed';
        iframe.style.top = '0';
        iframe.style.left = '0';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        iframe.style.zIndex = '9999999999999999';

        // Listen for message from iframe
        window.addEventListener('message', function(event) {
            // Verify the message is from our iframe
            if (event.data === 'close_artisan_editor') {
                document.body.removeChild(iframe);
                // Refresh the selected block on close
                if (window.blockHelpers) {
                    window.blockHelpers.refresh();
                }
            }
        });

        document.body.appendChild(iframe);
    };
})();

class BlockHelpers {
    constructor() {
        this.initialized = false;
        this.init();
    }

    init() {
        if (this.initialized) return;
        this.initialized = true;
        window.blockHelpers = this;
    }

    getSelectedBlock() {
        if (!wp || !wp.data) return null;
        return wp.data.select('core/block-editor').getSelectedBlock();
    }

    refresh() {
        if (!wp || !wp.data) {
            console.warn('WordPress Block Editor API not found');
            return;
        }

        const selectedBlock = this.getSelectedBlock();
        if (!selectedBlock) {
            console.warn('No block selected');
            return;
        }

        console.log('Changed Block', selectedBlock);

        try {
            // Get current attributes
            const currentAttributes = selectedBlock.attributes;
            
            // Initialize or increment change counter
            if (currentAttributes.change) {
                currentAttributes.change++;
            } else {
                currentAttributes.change = 1;
            }
            
            
            // Update attributes
            window.blockHelpers.updateAttributes(
                'change', currentAttributes.change
            );


        console.log('Block Refreshed Change Counter:', currentAttributes.change);
        
        


        } catch (error) {
            console.error('Error refreshing block:', error);
        }
    }

    updateBlockRegistry() {
        if (!wp || !wp.blocks || !wp.data) {
            console.warn('WordPress Block Editor API not found');
            return;
        }

        try {
            // Get all registered blocks
            const blocks = wp.blocks.getBlockTypes();
            
            // Find our client blocks
            blocks.forEach(block => {
                if (block.name.startsWith('acf/')) {  // Or whatever prefix identifies your blocks
                    // Temporarily unregister the block
                    wp.blocks.unregisterBlockType(block.name);
                    
                    // Re-register with updated settings
                    wp.blocks.registerBlockType(block.name, {
                        ...block,
                        // You can update any block settings here
                        // This forces WordPress to refresh its internal registry
                    });
                }
            });

            // Force an update of the editor store
            wp.data.dispatch('core/block-editor').synchronizeTemplate();
            
            console.log('Block registry updated successfully');
        } catch (error) {
            console.error('Error updating block registry:', error);
        }
    }

    updateAttributes(attributes) {
        const selectedBlock = this.getSelectedBlock();
        if (!selectedBlock) {
            console.warn('No block selected');
            return;
        }

        wp.data.dispatch('core/block-editor')
            .updateBlockAttributes(selectedBlock.clientId, attributes);
    }

    highlight() {
        const selectedBlock = this.getSelectedBlock();
        if (!selectedBlock) {
            console.warn('No block selected');
            return;
        }

        const blockElement = document.getElementById(`block-${selectedBlock.clientId}`);
        if (!blockElement) return;

        const originalOutline = blockElement.style.outline;
        const originalTransition = blockElement.style.transition;

        blockElement.style.outline = '2px solid #007cba';
        blockElement.style.transition = 'outline 0.2s ease';

        setTimeout(() => {
            blockElement.style.outline = originalOutline;
            blockElement.style.transition = originalTransition;
        }, 1500);
    }

    scrollTo() {
        const selectedBlock = this.getSelectedBlock();
        if (!selectedBlock) {
            console.warn('No block selected');
            return;
        }

        const blockElement = document.getElementById(`block-${selectedBlock.clientId}`);
        if (!blockElement) return;

        blockElement.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });

        this.highlight();
    }
}

// Initialize the helpers
new BlockHelpers();
