window.ClientBlocksMonaco = (function($) {
  const store = window.ClientBlocksStore;
  let monaco;
  
  const init = (monacoInstance) => {
    monaco = monacoInstance;
    setupTheme();
  };

  const setupTheme = () => {
    monaco.editor.defineTheme('vs-dark', {
      base: 'vs-dark',
      inherit: true,
      rules: [],
      colors: {
        'editor.background': '#1e1e1e'
      }
    });

    monaco.editor.setTheme('vs-dark');
  };

  const initializeEditors = () => {
    const monacoTabs = [...ClientBlocksTabs.mainTabs, ...ClientBlocksTabs.utilityTabs]
      .filter(tab => tab.editor === 'monaco' || !tab.editor);

    const editors = {};

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
        value: store.getState().editorStore[tab.id] || ''
      });

      editors[tab.id].onDidChangeModelContent(_.debounce(() => {
        store.updateEditorStore(tab.id, editors[tab.id].getValue());
        window.ClientBlocksEditor.updatePreview();
      }, 500));
    });

    store.setEditors(editors);

    const defaultTab = monacoTabs.find(tab => tab.defaultActive);
    if (defaultTab) {
      $(`#monaco-${defaultTab.id}`).show();
      store.setCurrentTab(defaultTab.id);
    }
  };

  const setEditorValue = (tabId, value) => {
    const editors = store.getState().editors;
    if (editors[tabId]) {
      editors[tabId].setValue(value || '');
    }
  };

  const getEditorValue = (tabId) => {
    const editors = store.getState().editors;
    return editors[tabId] ? editors[tabId].getValue() : '';
  };

  return {
    init,
    initializeEditors,
    setEditorValue,
    getEditorValue
  };
})(jQuery);
