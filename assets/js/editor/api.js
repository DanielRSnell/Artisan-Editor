window.ClientBlocksAPI = (function($) {
  return {
    async loadBlock(editorStore, setStatus, updateEditor, updatePreview) {
      try {
        setStatus('warning', 'Loading...');
        
        const response = await $.ajax({
          url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
          headers: { 'X-WP-Nonce': clientBlocksEditor.nonce }
        });
        
        for (const field in response.fields) {
          if (editorStore.hasOwnProperty(field.replace('client_', ''))) {
            editorStore[field.replace('client_', '')] = this.unescapeHTML(response.fields[field]);
          }
        }
        
        const globalCssResponse = await $.ajax({
          url: `${clientBlocksEditor.restUrl}/global-css`,
          headers: { 'X-WP-Nonce': clientBlocksEditor.nonce }
        });
        
        editorStore['global-css'] = globalCssResponse.css;
        
        updateEditor();
        updatePreview();
        setStatus('success', 'Ready');
        
        return response;
      } catch (error) {
        console.error('Error loading block:', error);
        setStatus('error', 'Load failed');
        throw error;
      }
    },

    async saveBlock(currentTab, editorStore, setStatus) {
      const $saveButton = $('#save-block');
      
      try {
        $saveButton.prop('disabled', true);
        setStatus('warning', 'Saving...');
        
        if (currentTab === 'global-css') {
          await $.ajax({
            url: `${clientBlocksEditor.restUrl}/global-css`,
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': clientBlocksEditor.nonce
            },
            data: JSON.stringify({ css: editorStore['global-css'] })
          });
        } else {
          const dataToSave = {
            [`client_${currentTab}`]: editorStore[currentTab]
          };
          
          await $.ajax({
            url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': clientBlocksEditor.nonce
            },
            data: JSON.stringify(dataToSave)
          });
        }
        
        setStatus('success', 'Saved');
        return true;
      } catch (error) {
        console.error('Error saving:', error);
        setStatus('error', 'Save failed');
        throw error;
      } finally {
        $saveButton.prop('disabled', false);
      }
    },

    unescapeHTML(escapedString) {
      const textarea = document.createElement('textarea');
      textarea.innerHTML = escapedString;
      return textarea.value;
    }
  };
})(jQuery);
