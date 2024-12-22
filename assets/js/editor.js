const ClientBlocksEditor = (function($) {
    console.log('Initializing ClientBlocksEditor');

    window.variables = [
        '--primary-color', '--secondary-color', '--text-color', '--background-color',
        '--font-size-small', '--font-size-medium', '--font-size-large',
        '--spacing-small', '--spacing-medium', '--spacing-large',
        '--border-radius', '--box-shadow', '--transition-speed'
    ];

    window.classes = [
        'container', 'row', 'col', 'btn', 'btn-primary', 'btn-secondary',
        'card', 'card-body', 'form-group', 'form-control', 'nav', 'nav-item',
        'table', 'table-striped', 'alert', 'alert-success', 'alert-danger'
    ];

    console.log('Global variables defined:', window.variables);
    console.log('Global classes defined:', window.classes);

    let editor;
    let currentTab = 'php';
    let blockData = {};
    let contextEditor;
    let lastSavedContent = {};
    let lastPreviewContent = {};
    let isInitialLoad = true;
    
    const editorStore = {
        php: '',
        template: '',
        css: '',
        js: '',
        'global-css': ''
    };

    const monacoConfig = {
        base: {
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
            wrappingIndent: 'indent',
            quickSuggestions: true,
            suggestOnTriggerCharacters: true,
            acceptSuggestionOnCommitCharacter: true,
            wordBasedSuggestions: true,
            suggestSelection: 'recentlyUsed',
            suggest: {
                filterGraceful: true,
                showIcons: true,
                maxVisibleSuggestions: 12,
                selectionMode: 'never'
            }
        },
        context: {
            value: '',
            theme: 'vs-dark',
            language: 'json',
            readOnly: true,
            minimap: { enabled: false },
            automaticLayout: true,
            fontSize: 14,
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            backgroundColor: '#1e1e1e'
        }
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

                // const match = textUntilPosition.match(/class\s*=\s*["'][^"']*$/);
                // if (!match) {
                //     return { suggestions: [] };
                // }

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



    const initializePreviewScripts = () => {
        const iframe = document.getElementById('preview-frame');
        if (!iframe || !iframe.contentDocument) return false;

        const postContextScript = iframe.contentDocument.getElementById('post-context');
        const mockFieldsScript = iframe.contentDocument.getElementById('mock-fields');
        const blockContextScript = iframe.contentDocument.getElementById('block-context');

        if (!postContextScript || !mockFieldsScript || !blockContextScript) return false;

        mockFieldsScript.textContent = JSON.stringify(blockData.acf || {}, null, 2);
        postContextScript.textContent = JSON.stringify(blockData.timber_context || {}, null, 2);
        blockContextScript.textContent = JSON.stringify({
            id: blockData.id,
            name: blockData.slug,
            data: blockData.fields || {},
            is_preview: true,
            post_id: blockData.id,
            template_id: blockData.id,
            ...editorStore
        }, null, 2);

        return true;
    };

    const waitForIframeLoad = () => {
        return new Promise((resolve) => {
            const iframe = document.getElementById('preview-frame');
            if (!iframe) {
                resolve(false);
                return;
            }

            const checkIframe = () => {
                if (iframe.contentDocument && 
                    iframe.contentDocument.readyState === 'complete' && 
                    iframe.contentDocument.getElementById('post-context')) {
                    const initialized = initializePreviewScripts();
                    resolve(initialized);
                    return;
                }
                setTimeout(checkIframe, 50);
            };

            if (iframe.contentDocument && 
                iframe.contentDocument.readyState === 'complete' && 
                iframe.contentDocument.getElementById('post-context')) {
                const initialized = initializePreviewScripts();
                resolve(initialized);
                return;
            }

            iframe.addEventListener('load', () => {
                checkIframe();
            });
        });
    };

    const updateEditor = () => {
        if (!editor) return;
        const language = ClientBlocksLanguageConfig[currentTab];
        const model = editor.getModel();
        monaco.editor.setModelLanguage(model, language);
        editor.setValue(editorStore[currentTab] || '');
    };

    const updateContextEditor = (context) => {
        if (!contextEditor) return;
        contextEditor.setValue(JSON.stringify(context || {}, null, 2));
    };

    const handleTabClick = function() {
        const $tab = $(this);
        const newTab = $tab.data('tab');
        
        if (currentTab === newTab) return;
        
        $(ClientBlocksElements.tabs).removeClass('active');
        $tab.addClass('active');
        
        currentTab = newTab;
        
        $(ClientBlocksElements.topBarTitle).text($tab.data('title'));
        
        $(ClientBlocksElements.editor).hide();
        $(ClientBlocksElements.contextEditor).hide();
        $(ClientBlocksElements.acfForm).hide();
        $('#settings-container').hide();
        $(ClientBlocksElements.saveButton).show();
        
        if (currentTab === 'acf') {
            $(ClientBlocksElements.acfForm).show();
            $(ClientBlocksElements.saveButton).hide();
        } else if (currentTab === 'context') {
            $(ClientBlocksElements.contextEditor).show();
        } else if (currentTab === 'settings') {
            $('#settings-container').show();
            $(ClientBlocksElements.saveButton).hide();
        } else {
            $(ClientBlocksElements.editor).show();
            updateEditor();
        }
    };

    const updatePreviewWithBlockData = async () => {
        try {
            const scriptsInitialized = await waitForIframeLoad();
            if (!scriptsInitialized) return;

            if (isInitialLoad) {
                isInitialLoad = false;
                await window.ClientBlocksPreview.updatePreview(
                    editorStore,
                    blockData,
                    lastPreviewContent,
                    ClientBlocksStatus.setStatus
                ).then(context => {
                    updateContextEditor(context);
                    lastPreviewContent = { ...editorStore };
                });
            } else {
                debouncedUpdatePreview();
            }
        } catch (error) {
            ClientBlocksStatus.setStatus('error', 'Preview update failed');
        }
    };

    const debouncedUpdatePreview = _.debounce(() => {
        window.ClientBlocksPreview.updatePreview(
            editorStore,
            blockData,
            lastPreviewContent,
            ClientBlocksStatus.setStatus
        ).then(context => {
            updateContextEditor(context);
            lastPreviewContent = { ...editorStore };
        });
    }, 1000);

    const reloadBlock = async () => {
        try {
            const response = await ClientBlocksAPI.loadBlock(
                editorStore,
                ClientBlocksStatus.setStatus,
                updateEditor,
                updatePreviewWithBlockData
            );
            blockData = response;
            lastSavedContent = { ...editorStore };
            lastPreviewContent = { ...editorStore };
            await updatePreviewWithBlockData();
        } catch (error) {
            console.error('Error reloading block:', error);
            ClientBlocksStatus.setStatus('error', 'Failed to reload block');
        }
    };

    const globalSave = async () => {
        try {
            ClientBlocksStatus.setStatus('warning', 'Saving...');
            const response = await $.ajax({
                url: `${clientBlocksEditor.restUrl}/blocks/${clientBlocksEditor.blockId}/global-save`,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': clientBlocksEditor.nonce
                },
                data: JSON.stringify(editorStore)
            });
            
            lastSavedContent = { ...editorStore };
            ClientBlocksStatus.setStatus('success', 'All changes saved');
            updatePreviewWithBlockData();
        } catch (error) {
            console.error('Error saving:', error);
            ClientBlocksStatus.setStatus('error', 'Save failed');
        }
    };

    const init = () => {
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

            configureCssCompletion(monaco);
            configureHtmlCompletion(monaco);

            editor = monaco.editor.create($(ClientBlocksElements.editor)[0], {
                ...monacoConfig.base,
                language: ClientBlocksLanguageConfig[currentTab]
            });
            
            contextEditor = monaco.editor.create($(ClientBlocksElements.contextEditor)[0], {
                ...monacoConfig.context
            });
            
            ClientBlocksAPI.loadBlock(
                editorStore,
                ClientBlocksStatus.setStatus,
                updateEditor,
                async () => {
                    await updatePreviewWithBlockData();
                }
            ).then(response => {
                blockData = response;
                lastSavedContent = { ...editorStore };
                lastPreviewContent = { ...editorStore };
            });
            
            $(ClientBlocksElements.tabs).on('click', handleTabClick);
            $(ClientBlocksElements.saveButton).on('click', () => {
                ClientBlocksAPI.saveBlock(
                    currentTab,
                    editorStore,
                    ClientBlocksStatus.setStatus
                ).then(() => {
                    lastSavedContent = { ...editorStore };
                    updatePreviewWithBlockData();
                });
            });

            $('#global-save-button').on('click', globalSave);

            $(ClientBlocksElements.editor).show();
            $('#context-editor').hide();
            $(ClientBlocksElements.acfForm).hide();
            $('.editor-top-bar-title').text('PHP Logic');
            
            $(document).on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    globalSave();
                }
            });
            
            editor.onDidChangeModelContent(() => {
                editorStore[currentTab] = editor.getValue();
                if (window.ClientBlocksPreview.hasContentChanged(editorStore, lastSavedContent)) {
                    ClientBlocksStatus.setStatus('warning', 'Unsaved changes');
                }
                updatePreviewWithBlockData();
            });

            $(ClientBlocksElements.preview).on('load', () => {
                if (isInitialLoad) {
                    updatePreviewWithBlockData();
                }
            });
        });

        window.ClientBlocksEditor = {
            reloadBlock: reloadBlock,
            globalSave: globalSave
        };
    };

    return {
        init
    };
})(jQuery);

jQuery(document).ready(function() {
    ClientBlocksEditor.init();
});