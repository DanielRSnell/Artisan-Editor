const ClientBlocksEditor = (function($) {
  let editors = {};
  let currentTab = 'template';
  let blockData = {};
  let lastSavedContent = {};
  let isInitialLoad = true;
  
  const editorStore = {
    template: '',
    php: '',
    'block-json': '',
    'block-css': '',
    'block-scripts': '',
    'global-css': '',
    'global-js': '',
    context: '{}'
  };

  const updatePreview = _.debounce(() => {
    if (window.ClientBlocksPreview) {
      window.ClientBlocksPreview.updatePreview(editorStore, blockData)
        .then(context => {
          if (editors.context) {
            editors.context.setValue(JSON.stringify(context || {}, null, 2));
          }
        })
        .catch(error => {
          console.error('Preview update failed:', error);
          ClientBlocksStatus.setStatus('error', 'Preview update failed');
        });
    } else {
      console.warn('Preview module not initialized');
    }
  }, 1000);

  const init = () => {
    require.config({ paths: { vs: clientBlocksEditor.monacoPath }});
    
    require(['vs/editor/editor.main'], () => {
      monaco.editor.defineTheme('vs-dark', {
        base: 'vs-dark',
        inherit: true,
        rules: [],
        colors: {
          'editor.background': '#1e1e1e'
        }
      });

      monaco.editor.setTheme('vs-dark');
      
      initializeEditors();
      loadBlock();
      
      $(ClientBlocksElements.saveButton).on('click', saveBlock);
      $('#global-save-button').on('click', globalSave);
      
      $(document).on('click', '.tab-button', handleTabClick);
    });
  };

  const initializeEditors = () => {
    const monacoTabs = [...ClientBlocksTabs.mainTabs, ...ClientBlocksTabs.utilityTabs]
      .filter(tab => tab.editor === 'monaco' || !tab.editor);

    monacoTabs.forEach(tab => {
      const container = document.createElement('div');
      container.id = `monaco-${tab.id}`;
      container.style.width = '100%';
      container.style.height = '100%';
      container.style.display = 'none';
      document.getElementById('monaco-editor').appendChild(container);

      editors[tab.id] = monaco.editor.create(container, {
        ...ClientBlocksConfig.editorOptions,
        language: tab.language,
        value: editorStore[tab.id] || ''
      });

      editors[tab.id].onDidChangeModelContent(_.debounce(() => {
        editorStore[tab.id] = editors[tab.id].getValue();
        updatePreview();
      }, 500));
    });

    const defaultTab = monacoTabs.find(tab => tab.defaultActive);
    if (defaultTab) {
      $(`#monaco-${defaultTab.id}`).show();
      currentTab = defaultTab.id;
    }
  };

  const handleTabClick = function() {
    const $tab = $(this);
    const newTab = $tab.data('tab');
    const editor = $tab.data('editor') || 'monaco';
    
    if (currentTab === newTab) return;
    
    $('#monaco-editor > div').hide();
    $('#context-editor').hide();
    $('#acf-form-container').hide();
    $('#settings-container').hide();
    
    currentTab = newTab;
    
    switch(editor) {
      case 'monaco':
        $(`#monaco-${newTab}`).show();
        if (editors[newTab]) {
          editors[newTab].layout();
          editors[newTab].setValue(editorStore[newTab] || '');
        }
        break;
      case 'form':
        $('#acf-form-container').show();
        break;
      case 'custom':
        $('#settings-container').show();
        break;
    }
  };

  const loadBlock = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Loading...');
      
      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
        method: 'GET',
        headers: { 'X-WP-Nonce': clientBlocksEditor.nonce }
      });
      
      blockData = response;
      
      editorStore.template = response.fields.template || '';
      editorStore.php = response.fields.php || '';
      editorStore['block-json'] = response.fields['block-json'] || '{}';
      editorStore['block-css'] = response.fields.css || '';
      editorStore['block-scripts'] = response.fields.js || '';
      editorStore['global-css'] = response['global-css'] || '';
      editorStore['global-js'] = response['global-js'] || '';
      
      Object.keys(editors).forEach(tabId => {
        editors[tabId].setValue(editorStore[tabId] || '');
      });
      
      updatePreview();
      ClientBlocksStatus.setStatus('success', 'Ready');
      
      lastSavedContent = { ...editorStore };
    } catch (error) {
      console.error('Error loading block:', error);
      ClientBlocksStatus.setStatus('error', 'Load failed');
    }
  };

  const saveBlock = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Saving...');
      
      const dataToSave = {
        [currentTab]: editors[currentTab].getValue()
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
      
      editorStore[currentTab] = editors[currentTab].getValue();
      lastSavedContent = { ...editorStore };
      ClientBlocksStatus.setStatus('success', 'Saved');
      updatePreview();
    } catch (error) {
      console.error('Error saving:', error);
      ClientBlocksStatus.setStatus('error', 'Save failed');
    }
  };

  const globalSave = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Saving all changes...');
      
      const dataToSave = {};
      Object.keys(editors).forEach(tabId => {
        dataToSave[tabId] = editors[tabId].getValue();
      });
      
      await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}/global-save`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify(dataToSave)
      });
      
      Object.keys(editors).forEach(tabId => {
        editorStore[tabId] = editors[tabId].getValue();
      });
      
      lastSavedContent = { ...editorStore };
      ClientBlocksStatus.setStatus('success', 'All changes saved');
      updatePreview();
    } catch (error) {
      console.error('Error saving:', error);
      ClientBlocksStatus.setStatus('error', 'Save failed');
    }
  };

  return {
    init,
    loadBlock,
    globalSave,
    updatePreview
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksEditor.init();
});
