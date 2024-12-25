const ClientBlocksEditor = (function($) {
  let editors = {};
  let currentTab = 'template';
  let blockData = {};
  let lastSavedContent = {};
  let lastPayload = null;
  let isUpdating = false;
  
  window.editorStore = {
    template: '',
    php: '',
    'block-json': '',
    'block-css': '',
    'block-scripts': '',
    'global-css': '',
    'global-js': '',
    context: '{}'
  };

  const createPreviewPayload = (editorStore, blockData, previewContext) => {
    return {
      block_id: blockData.id,
      template: editorStore.template,
      php: editorStore.php,
      css: editorStore['block-css'],
      js: editorStore['block-scripts'],
      json: editorStore['block-json'],
      align: JSON.parse(editorStore['block-json'] || '{}').align || '',
      className: JSON.parse(editorStore['block-json'] || '{}').className || '',
      mode: 'preview',
      supports: JSON.parse(editorStore['block-json'] || '{}').supports || {},
      preview_context: previewContext
    };
  };

  const hasPayloadChanged = (newPayload) => {
    if (!lastPayload) return true;
    return JSON.stringify(newPayload) !== JSON.stringify(lastPayload);
  };

  const debouncedPreviewUpdate = _.debounce(async (payload) => {
    if (isUpdating) return;
    isUpdating = true;

    try {
      const context = await window.ClientBlocksPreview.updatePreview(
        editorStore, 
        blockData, 
        payload.preview_context
      );

      if (editors.context) {
        editors.context.setValue(JSON.stringify(context, null, 2));
        window.updateWindpress();
      }

      lastPayload = payload;
      isUpdating = false;
      return context;
    } catch (error) {
      console.error('Preview update failed:', error);
      ClientBlocksStatus.setStatus('error', 'Preview update failed');
      isUpdating = false;
      throw error;
    }
  }, 500);

  const updatePreview = async () => {
    if (!window.ClientBlocksPreview) return;

    const previewContext = window.ClientBlocksPreviewContext?.getCurrentContext() || { 
      type: 'home', 
      preview_path: 'home' 
    };

    const newPayload = createPreviewPayload(editorStore, blockData, previewContext);
    
    if (!hasPayloadChanged(newPayload)) {
      return lastPayload?.context;
    }

    return debouncedPreviewUpdate(newPayload);
  };

  const init = () => {
    window.ClientBlocksEditor = {
      updatePreview: updatePreview,
      init: init,
      loadBlock: loadBlock,
      globalSave: globalSave
    };

    require.config({ paths: { vs: ClientBlocksConfig.monacoPath }});
    
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

      if (window.ClientBlocksCompleters) {
        ClientBlocksCompleters.configureCssCompletion(monaco);
        ClientBlocksCompleters.configureHtmlCompletion(monaco);
      }
      
      $(ClientBlocksElements.saveButton).on('click', saveBlock);
      $('#global-save-button').on('click', globalSave);
      
      $(document).on('click', '.tab-button', handleTabClick);

      if (window.ClientBlocksPreviewContext) {
        window.ClientBlocksPreviewContext.setContextChangeHandler(() => {
          updatePreview();
        });
      }
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
    $('#preview-context-container').hide();
    
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
        if (newTab === 'preview-context') {
          $('#preview-context-container').show();
        } else {
          $('#settings-container').show();
        }
        break;
    }
  };

  const loadBlock = async () => {
    try {
      ClientBlocksStatus.setStatus('warning', 'Loading...');
      
      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}`,
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
        if (editors[tabId]) {
          let content = '';
          switch(tabId) {
            case 'block-css':
              content = response.fields.css || '';
              break;
            case 'block-scripts':
              content = response.fields.js || '';
              break;
            default:
              content = editorStore[tabId] || '';
          }
          editors[tabId].setValue(content);
        }
      });
      
      if (window.ClientBlocksPreviewContext) {
        await window.ClientBlocksPreviewContext.init();
      }
      
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
      
      const dataToSave = {};
      
      switch(currentTab) {
        case 'block-css':
          dataToSave.css = editors[currentTab].getValue();
          break;
        case 'block-scripts':
          dataToSave.js = editors[currentTab].getValue();
          break;
        default:
          dataToSave[currentTab] = editors[currentTab].getValue();
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
      
      const dataToSave = {
        template: editors.template?.getValue() || '',
        php: editors.php?.getValue() || '',
        'block-json': editors['block-json']?.getValue() || '{}',
        css: editors['block-css']?.getValue() || '',
        js: editors['block-scripts']?.getValue() || '',
        'global-css': editors['global-css']?.getValue() || '',
        'global-js': editors['global-js']?.getValue() || ''
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
      
      Object.keys(editors).forEach(tabId => {
        if (editors[tabId]) {
          editorStore[tabId] = editors[tabId].getValue();
        }
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
