window.ClientBlocksPreview = (function($) {
  let lastPreviewContent = {};

  const updatePreview = async (editorStore, blockData, previewContext) => {
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

      if (hasContentChanged(currentContent, lastPreviewContent)) {
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
            supports: blockJson.supports || {},
            preview_context: previewContext
          })
        });

        editorContent.innerHTML = response.content;
        initializePreviewBlock(iframe, blockData.id);
        lastPreviewContent = { ...currentContent };
        return response.context;
      }
    } catch (error) {
      console.error('Error updating preview:', error);
      throw error;
    }
  };

  const initializePreviewBlock = (iframe, blockId) => {
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
  };

  const hasContentChanged = (newContent, lastContent) => {
    return JSON.stringify(newContent) !== JSON.stringify(lastContent);
  };

  return {
    updatePreview,
    initializePreviewBlock,
    hasContentChanged
  };
})(jQuery);


window.updateWindpress = () => {
    const iframe = document.getElementById('preview-frame');
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