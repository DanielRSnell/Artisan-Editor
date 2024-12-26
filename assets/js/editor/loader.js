window.ClientBlocksLoader = (function($) {
  const store = window.ClientBlocksStore;
  const monaco = window.ClientBlocksMonaco;

  const loadBlock = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Loading...');
      
      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
        headers: { 'X-WP-Nonce': clientBlocksEditor.nonce }
      });
      
      store.setState({ blockData: response });
      
      // Load block fields
      store.updateEditorStore('template', response.fields.template || '');
      store.updateEditorStore('php', response.fields.php || '');
      store.updateEditorStore('block-json', response.fields['block-json'] || '{}');
      store.updateEditorStore('block-css', response.fields.css || '');
      store.updateEditorStore('block-scripts', response.fields.js || '');

      // Set initial global file content
      if (response.global_files) {
        const defaultCssFile = response.global_files.css.find(f => f.name === 'main.css') || response.global_files.css[0];
        const defaultJsFile = response.global_files.js.find(f => f.name === 'main.js') || response.global_files.js[0];

        if (defaultCssFile) {
          store.updateEditorStore('global-css', defaultCssFile.content);
        }
        if (defaultJsFile) {
          store.updateEditorStore('global-js', defaultJsFile.content);
        }
      }
      
      // Set editor values
      Object.keys(store.getState().editors).forEach(tabId => {
        if (store.getState().editors[tabId]) {
          monaco.setEditorValue(tabId, store.getState().editorStore[tabId] || '');
        }
      });
      
      // Initialize global files if present
      if (response.global_files) {
        await ClientBlocksGlobalFiles.init(response.global_files);
      }
      
      window.ClientBlocksEditor.updatePreview();
      ClientBlocksStatus.setStatus('success', 'Ready');
      
      store.setState({ lastSavedContent: { ...store.getState().editorStore } });
    } catch (error) {
      console.error('Error loading block:', error);
      ClientBlocksStatus.setStatus('error', 'Load failed');
    }
  };

  return {
    loadBlock
  };
})(jQuery);
