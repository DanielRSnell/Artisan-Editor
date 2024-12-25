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

window.ClientBlocksTabs = {
  mainTabs: [
    {
      id: 'template',
      title: 'TWIG Template',
      icon: 'document-text-outline',
      language: 'html',
      defaultActive: true
    },
    {
      id: 'php',
      title: 'PHP Logic',
      icon: 'code-slash-outline',
      language: 'php'
    },
    {
      id: 'block-json',
      title: 'Block JSON',
      icon: 'code-working-outline',
      language: 'json'
    },
    {
      id: 'block-css',
      title: 'Block CSS',
      icon: 'brush-outline',
      language: 'css'
    },
    {
      id: 'block-scripts',
      title: 'Block Scripts',
      icon: 'logo-javascript',
      language: 'javascript'
    }
  ],
  utilityTabs: [
    {
      id: 'preview-context',
      title: 'Preview Context',
      icon: 'eye-outline',
      editor: 'custom'
    },
    {
      id: 'context',
      title: 'Context Inspector',
      icon: 'code-outline',
      language: 'json',
      editor: 'monaco'
    },
    {
      id: 'acf',
      title: 'Mock Block Controls',
      icon: 'construct-outline',
      editor: 'form'
    },
    {
      id: 'global-css',
      title: 'Global CSS',
      icon: 'brush-outline',
      language: 'css',
      editor: 'monaco'
    },
    {
      id: 'global-js',
      title: 'Global JS',
      icon: 'globe-outline',
      language: 'javascript',
      editor: 'monaco'
    }
  ],
  bottomTabs: [
    {
      id: 'settings',
      title: 'Editor Settings',
      icon: 'settings-outline',
      editor: 'custom'
    }
  ]
};

window.ClientBlocksElements = {
  editor: '#monaco-editor',
  preview: '#preview-frame',
  saveButton: '#save-block',
  tabs: '.tab-button',
  acfForm: '#acf-form-container',
  contextEditor: '#context-editor',
  previewContext: '#preview-context-container',
  topBarTitle: '.editor-top-bar-title',
  statusIndicator: '.editor-status-indicator',
  statusText: '.editor-status-text'
};
