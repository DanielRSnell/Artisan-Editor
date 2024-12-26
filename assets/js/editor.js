const ClientBlocksEditor = (function($) {
  const store = window.ClientBlocksStore;
  const monaco = window.ClientBlocksMonaco;
  const save = window.ClientBlocksSave;
  const loader = window.ClientBlocksLoader;

  const init = () => {
    window.ClientBlocksEditor = {
      updatePreview: updatePreview,
      init: init,
      loadBlock: loader.loadBlock,
      globalSave: save.globalSave
    };

    require.config({ paths: { vs: ClientBlocksConfig.monacoPath }});
    
    require(['vs/editor/editor.main'], function(monacoInstance) {
      // Initialize Monaco instance
      monaco.init(monacoInstance);
      
      // Initialize editors
      monaco.initializeEditors();
      
      // Load block data
      loader.loadBlock();

      if (window.ClientBlocksCompleters) {
        ClientBlocksCompleters.configureCssCompletion(monacoInstance);
        ClientBlocksCompleters.configureHtmlCompletion(monacoInstance);
      }
      
      // Setup event handlers
      $(ClientBlocksElements.saveButton).on('click', save.saveBlock);
      $('#global-save-button').on('click', save.globalSave);
      $(document).on('click', '.tab-button', handleTabClick);

      if (window.ClientBlocksPreviewContext) {
        window.ClientBlocksPreviewContext.setContextChangeHandler(() => {
          updatePreview();
        });
      }
    });
  };

  const handleTabClick = function() {
    const $tab = $(this);
    const newTab = $tab.data('tab');
    const editor = $tab.data('editor') || 'monaco';
    
    if (store.getCurrentTab() === newTab) return;
    
    $('#monaco-editor > div').hide();
    $('#context-editor').hide();
    $('#acf-form-container').hide();
    $('#settings-container').hide();
    $('#preview-context-container').hide();
    
    store.setCurrentTab(newTab);
    
    switch(editor) {
      case 'monaco':
        $(`#monaco-${newTab}`).show();
        if (store.getState().editors[newTab]) {
          store.getState().editors[newTab].layout();
          monaco.setEditorValue(newTab, store.getState().editorStore[newTab] || '');
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

  const createPreviewPayload = () => {
    const state = store.getState();
    return {
      block_id: state.blockData.id,
      template: state.editorStore.template,
      php: state.editorStore.php,
      css: state.editorStore['block-css'],
      js: state.editorStore['block-scripts'],
      json: state.editorStore['block-json'],
      align: JSON.parse(state.editorStore['block-json'] || '{}').align || '',
      className: JSON.parse(state.editorStore['block-json'] || '{}').className || '',
      mode: 'preview',
      supports: JSON.parse(state.editorStore['block-json'] || '{}').supports || {},
      preview_context: window.ClientBlocksPreviewContext?.getCurrentContext() || { 
        type: 'home', 
        preview_path: 'home' 
      }
    };
  };

  const updatePreview = _.debounce(async () => {
    if (!window.ClientBlocksPreview || store.getState().isUpdating) return;

    const payload = createPreviewPayload();
    
    if (JSON.stringify(payload) === JSON.stringify(store.getState().lastPayload)) {
      return store.getState().lastPayload?.context;
    }

    store.setState({ isUpdating: true });

    try {
      const context = await window.ClientBlocksPreview.updatePreview(
        store.getState().editorStore, 
        store.getState().blockData,
        payload.preview_context
      );

      if (store.getState().editors.context) {
        monaco.setEditorValue('context', JSON.stringify(context, null, 2));
        window.updateWindpress();
      }

      store.setState({ 
        lastPayload: payload,
        isUpdating: false 
      });
      
      return context;
    } catch (error) {
      console.error('Preview update failed:', error);
      ClientBlocksStatus.setStatus('error', 'Preview update failed');
      store.setState({ isUpdating: false });
      throw error;
    }
  }, 500);

  return {
    init,
    updatePreview
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksEditor.init();
});
