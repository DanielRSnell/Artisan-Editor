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

  const configureCssCompletion = (monaco) => {
        monaco.languages.registerCompletionItemProvider('css', {
            triggerCharacters: ['"', "'", ' '],
            provideCompletionItems: (model, position) => {
                const textUntilPosition = model.getValueInRange({
                    startLineNumber: 1,
                    startColumn: 1,
                    endLineNumber: position.lineNumber,
                    endColumn: position.column
                });

                const word = model.getWordUntilPosition(position);
                const range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: word.startColumn,
                    endColumn: word.endColumn
                };

                return {
                    suggestions: window.variables.map(variable => ({
                        label: variable,
                        kind: monaco.languages.CompletionItemKind.Value,
                        insertText: variable,
                        range: range,
                        filterText: variable,
                        sortText: variable
                    }))
                };
            }
        });
                console.log('✅ CSS completion provider configured');

    };

    const configureHtmlCompletion = (monaco) => {
        monaco.languages.registerCompletionItemProvider('html', {
            triggerCharacters: ['"', "'", ' ', '-'],
            provideCompletionItems: (model, position) => {
                console.log('🎯 Completion provider triggered at position:', position);

                const textUntilPosition = model.getValueInRange({
                    startLineNumber: 1,
                    startColumn: 1,
                    endLineNumber: position.lineNumber,
                    endColumn: position.column
                });
                console.log('📝 Text until position:', textUntilPosition);

                // Check for class attribute context
                const classMatch = textUntilPosition.match(/class\s*=\s*["']([^"']*)$/);
                console.log('🎨 Class attribute match:', classMatch);
                
                // Simplified hyphen detection
                const withinVarPattern = /var\(\s*-$/;
                const singleHyphenPattern = /-$/;
                
                const isWithinVar = withinVarPattern.test(textUntilPosition);
                const hasSingleHyphen = singleHyphenPattern.test(textUntilPosition);
                
                console.log('🔍 Hyphen context:', { 
                    isWithinVar,
                    hasSingleHyphen,
                    textEndsWithHyphen: textUntilPosition.endsWith('-'),
                    lastFiveChars: textUntilPosition.slice(-5)
                });

                const word = model.getWordUntilPosition(position);
                console.log('📊 Current word:', word);

                const range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: word.startColumn,
                    endColumn: word.endColumn
                };
                console.log('📏 Completion range:', range);

                // Show CSS variable suggestions for any hyphen
                if (hasSingleHyphen) {
                    const reason = isWithinVar ? 'within var()' : 'single hyphen';
                    console.log('🎨 Providing CSS variable suggestions', { reason });
                    
                    const suggestions = window.variables.map(variable => {
                        const varName = variable.startsWith('--') ? variable : `--${variable}`;
                        const insertText = reason === 'single hyphen' ? 
                            `var(${varName})` : // Wrap in var() for single hyphen
                            varName;            // Just the variable name within var()
                        
                        return {
                            label: variable,
                            kind: monaco.languages.CompletionItemKind.Variable,
                            insertText,
                            range: range,
                            filterText: variable,
                            sortText: variable,
                            documentation: `CSS Variable: ${variable}`
                        };
                    });
                    console.log('📝 Generated CSS variable suggestions:', suggestions);
                    
                    return { suggestions };
                }

                // Handle class suggestions
                if (classMatch) {
                    console.log('🎯 Processing class suggestions');
                    const currentClasses = classMatch[1].split(' ');
                    const lastClass = currentClasses[currentClasses.length - 1];
                    console.log('Current classes:', currentClasses);
                    console.log('Last class typed:', lastClass);

                    const filteredClasses = window.classes.filter(className => {
                        const isUsed = currentClasses.slice(0, -1).includes(className);
                        const matchesFilter = !lastClass || className.toLowerCase().includes(lastClass.toLowerCase());
                        return !isUsed && matchesFilter;
                    });
                    console.log('🔍 Filtered classes:', filteredClasses);

                    const suggestions = filteredClasses.map(className => ({
                        label: className,
                        kind: monaco.languages.CompletionItemKind.Value,
                        insertText: className,
                        range: range,
                        filterText: className,
                        sortText: className,
                        documentation: `Tailwind Class: ${className}`
                    }));
                    console.log('📝 Generated class suggestions:', suggestions);

                    return { suggestions };
                }

                console.log('⚠️ No matching context found, returning empty suggestions');
                return { suggestions: [] };
            }
        });
        console.log('✅ HTML completion provider configured');
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

      configureCssCompletion(monaco);
      configureHtmlCompletion(monaco);
      
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
