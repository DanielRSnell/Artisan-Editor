window.ClientBlocksConfig = {
  monacoPath: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs',
  editorOptions: {
    value: '',
    theme: 'vs-dark',
    minimap: { enabled: true },
    automaticLayout: true,
    fontSize: 14,
    lineNumbers: 'on',
    scrollBeyondLastLine: false,
    wordWrap: 'on',
    formatOnPaste: true,
    formatOnType: true,
    wrappingIndent: 'indent'
  }
};

window.ClientBlocksLanguageConfig = {
  php: 'php',
  template: 'html',
  css: 'css',
  js: 'javascript',
  context: 'json',
  'global-css': 'css'
};

window.ClientBlocksElements = {
  editor: '#monaco-editor',
  preview: '#preview-frame',
  saveButton: '#save-block',
  tabs: '.tab-button',
  acfForm: '#acf-form-container',
  contextEditor: '#context-editor',
  topBarTitle: '.editor-top-bar-title',
  statusIndicator: '.editor-status-indicator',
  statusText: '.editor-status-text'
};
