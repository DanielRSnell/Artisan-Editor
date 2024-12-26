window.ClientBlocksStore = (function() {
  const state = {
    editorStore: {
      template: '',
      php: '',
      'block-json': '',
      'block-css': '',
      'block-scripts': '',
      'global-css': '',
      'global-js': '',
      context: '{}'
    },
    currentTab: 'template',
    blockData: {},
    lastSavedContent: {},
    lastPayload: null,
    isUpdating: false,
    editors: {}
  };

  const getState = () => state;
  const setState = (newState) => Object.assign(state, newState);
  
  const updateEditorStore = (key, value) => {
    state.editorStore[key] = value;
  };

  const setEditors = (editors) => {
    state.editors = editors;
  };

  const getCurrentTab = () => state.currentTab;
  const setCurrentTab = (tab) => {
    state.currentTab = tab;
  };

  return {
    getState,
    setState,
    updateEditorStore,
    setEditors,
    getCurrentTab,
    setCurrentTab
  };
})();
