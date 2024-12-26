window.ClientBlocksSave = (function($) {
  const store = window.ClientBlocksStore;
  const monaco = window.ClientBlocksMonaco;

  const saveBlock = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Saving...');
      
      const currentTab = store.getCurrentTab();
      
      // Handle global files separately
      if (currentTab === 'global-css' || currentTab === 'global-js') {
        await ClientBlocksGlobalFiles.saveCurrentFile();
        ClientBlocksStatus.setStatus('success', 'Saved');
        return;
      }
      
      const dataToSave = {};
      
      switch(currentTab) {
        case 'block-css':
          dataToSave.css = monaco.getEditorValue(currentTab);
          break;
        case 'block-scripts':
          dataToSave.js = monaco.getEditorValue(currentTab);
          break;
        default:
          dataToSave[currentTab] = monaco.getEditorValue(currentTab);
      }
      
      await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify(dataToSave)
      });
      
      store.updateEditorStore(currentTab, monaco.getEditorValue(currentTab));
      store.setState({ lastSavedContent: { ...store.getState().editorStore } });
      ClientBlocksStatus.setStatus('success', 'Saved');
      window.ClientBlocksEditor.updatePreview();
    } catch (error) {
      console.error('Error saving:', error);
      ClientBlocksStatus.setStatus('error', 'Save failed');
    }
  };

  const globalSave = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Saving all changes...');
      
      // Get current global files content
      const globalFiles = ClientBlocksGlobalFiles.getCurrentFiles();
      const currentCssFile = ClientBlocksGlobalFiles.getCurrentFile('css');
      const currentJsFile = ClientBlocksGlobalFiles.getCurrentFile('js');

      // Update current file content before saving
      if (currentCssFile) {
        const cssFileIndex = globalFiles.css.findIndex(f => f.name === currentCssFile.name);
        if (cssFileIndex !== -1) {
          globalFiles.css[cssFileIndex].content = monaco.getEditorValue('global-css');
        }
      }

      if (currentJsFile) {
        const jsFileIndex = globalFiles.js.findIndex(f => f.name === currentJsFile.name);
        if (jsFileIndex !== -1) {
          globalFiles.js[jsFileIndex].content = monaco.getEditorValue('global-js');
        }
      }
      
      const dataToSave = {
        template: monaco.getEditorValue('template'),
        php: monaco.getEditorValue('php'),
        'block-json': monaco.getEditorValue('block-json'),
        css: monaco.getEditorValue('block-css'),
        js: monaco.getEditorValue('block-scripts'),
        global_files: globalFiles
      };
      
      await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}/global-save`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify(dataToSave)
      });
      
      Object.keys(store.getState().editors).forEach(tabId => {
        if (store.getState().editors[tabId]) {
          store.updateEditorStore(tabId, monaco.getEditorValue(tabId));
        }
      });
      
      store.setState({ lastSavedContent: { ...store.getState().editorStore } });
      ClientBlocksStatus.setStatus('success', 'All changes saved');
      window.ClientBlocksEditor.updatePreview();
    } catch (error) {
      console.error('Error saving:', error);
      ClientBlocksStatus.setStatus('error', 'Save failed');
    }
  };

  return {
    saveBlock,
    globalSave
  };
})(jQuery);
