window.ClientBlocksPreview = (function($) {
  return {
    hasContentChanged(newContent, lastContent) {
      return JSON.stringify(newContent) !== JSON.stringify(lastContent);
    },

    async updatePreview(editorStore, blockData, lastPreviewContent, setStatus) {
      try {
        // setStatus('warning', 'Updating preview...');
        
        const iframe = document.getElementById('preview-frame');
        if (!iframe || !iframe.contentDocument) {
          throw new Error('Preview frame not ready');
        }

        const postContext = JSON.parse(iframe.contentDocument.getElementById('post-context').textContent || '{}');
        const mockFields = JSON.parse(iframe.contentDocument.getElementById('mock-fields').textContent || '{}');
        const blockContext = JSON.parse(iframe.contentDocument.getElementById('block-context').textContent || '{}');
        
        const response = await $.ajax({
          url: `${clientBlocksEditor.restUrl}/preview`,
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': clientBlocksEditor.nonce
          },
          data: JSON.stringify({
            block_id: clientBlocksEditor.blockId,
            post_context: JSON.stringify(blockData.timber_context || {}),
            mock_fields: JSON.stringify(blockData.acf || {}),
            block_context: JSON.stringify({
              ...blockContext,
              data: blockData.fields || {},
              template_id: blockData.id,
              ...editorStore
            })
          })
        });
        
        const editorContent = iframe.contentDocument.getElementById('editor-content');
        if (editorContent) {
          editorContent.innerHTML = response.content;
        }
        
        // setStatus('success', 'Preview updated');
        return response.context;
      } catch (error) {
        console.error('Error updating preview:', error);
        setStatus('error', 'Preview update failed');
        throw error;
      }
    }
  };
})(jQuery);
