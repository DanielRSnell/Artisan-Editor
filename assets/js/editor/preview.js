window.ClientBlocksPreview = (function($) {
  let lastPreviewContent = {};

  const toggleActivationIndicator = (iframe) => {
    if (!iframe.contentWindow) return;
    
    const vfsElement = iframe.contentDocument.querySelector('#windpress\\:vfs');
    if (!vfsElement) return;
    
    try {
      iframe.contentWindow.postMessage({
        source: 'windpress/dashboard',
        target: 'windpress/observer',
        task: 'windpress.code-editor.saved',
        payload: {
          volume: JSON.parse(atob(vfsElement.textContent)),
          comment: 'Activation toggle'
        }
      }, '*');
    } catch (error) {
      console.error('Error triggering Windpress update:', error);
    }
  };

  const updateContextEditor = (context) => {
    const context_string = JSON.stringify(context, null, 2);
    window.editorStore.context = context_string;
  };

  return {
    async updatePreview(editorStore, blockData) {
      try {
        const iframe = document.getElementById('preview-frame');
        if (!iframe || !iframe.contentDocument) {
          throw new Error('Preview frame not ready');
        }

        const editorContent = iframe.contentDocument.getElementById('editor-content');
        if (!editorContent) {
          throw new Error('Editor content container not found');
        }

        const currentContent = {
          template: editorStore.template,
          php: editorStore.php,
          'block-css': editorStore['block-css'],
          'block-scripts': editorStore['block-scripts'],
          'block-json': editorStore['block-json']
        };

        if (this.hasContentChanged(currentContent, lastPreviewContent)) {
          let blockJson = {};
          try {
            blockJson = JSON.parse(editorStore['block-json']);
          } catch (e) {
            console.warn('Invalid block JSON:', e);
          }

          const response = await $.ajax({
            url: `${clientBlocksEditor.restUrl}/preview`,
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': clientBlocksEditor.nonce
            },
            data: JSON.stringify({
              block_id: blockData.id,
              template: editorStore.template,
              php: editorStore.php,
              css: editorStore['block-css'],
              js: editorStore['block-scripts'],
              json: editorStore['block-json'],
              align: blockJson.align || '',
              className: blockJson.className || '',
              mode: 'preview',
              supports: blockJson.supports || {}
            })
          });

          // Update the preview content
          editorContent.innerHTML = response.content;

          // Toggle the activation indicator
          toggleActivationIndicator(iframe);

          // Initialize block in preview
          this.initializePreviewBlock(iframe, blockData.id);

          // Update both editorStore and context editor
          updateContextEditor(response.context);

          lastPreviewContent = { ...currentContent };
          return response.context;
        }
      } catch (error) {
        console.error('Error updating preview:', error);
        throw error;
      }
    },

    initializePreviewBlock(iframe, blockId) {
      const script = iframe.contentDocument.createElement('script');
      script.textContent = `
        (function() {
          const block = document.getElementById('block-${blockId}');
          if (!block) return;
          
          const event = new CustomEvent('block-ready', { 
            detail: { 
              blockId: '${blockId}',
              isPreview: true 
            }
          });
          block.dispatchEvent(event);
          
          block.addEventListener('click', function(e) {
            if (e.target.tagName === 'A') {
              e.preventDefault();
            }
          });
        })();
      `;
      iframe.contentDocument.body.appendChild(script);
    },

    hasContentChanged(newContent, lastContent) {
      return JSON.stringify(newContent) !== JSON.stringify(lastContent);
    }
  };
})(jQuery);

// Initialize preview module when document is ready
jQuery(document).ready(function() {
  if (window.ClientBlocksPreview && typeof window.ClientBlocksPreview.init === 'function') {
    window.ClientBlocksPreview.init();
  }
});
