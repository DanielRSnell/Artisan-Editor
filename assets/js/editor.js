const ClientBlocksEditor = (function($) {
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

            editor = monaco.editor.create($(ClientBlocksElements.editor)[0], {
                ...ClientBlocksConfig.editorOptions,
                language: ClientBlocksLanguageConfig[currentTab]
            });
            
            contextEditor = monaco.editor.create($(ClientBlocksElements.contextEditor)[0], {
                ...ClientBlocksConfig.contextEditorOptions
            });
            
            loadBlock();
            
            $(ClientBlocksElements.tabs).on('click', handleTabClick);
            $(ClientBlocksElements.saveButton).on('click', saveBlock);

            $('#global-save-button').on('click', globalSave);

            $(ClientBlocksElements.editor).show();
            $('#context-editor').hide();
            $(ClientBlocksElements.acfForm).hide();
            $('.editor-top-bar-title').text('PHP Logic');
            
            editor.onDidChangeModelContent(() => {
                editorStore[currentTab] = editor.getValue();
                if (ClientBlocksPreview.hasContentChanged(editorStore, lastSavedContent)) {
                    ClientBlocksStatus.setStatus('warning', 'Unsaved changes');
                }
                updatePreview();
            });

            $(ClientBlocksElements.preview).on('load', () => {
                if (isInitialLoad) {
                    updatePreview();
                }
            });
        });
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
            editorStore.php = response.fields.php || '';
            editorStore.template = response.fields.template || '';
            editorStore.css = response.fields.css || '';
            editorStore.js = response.fields.js || '';
            editorStore['global-css'] = response['global-css'] || '';
            
            updateEditor();
            updatePreview();
            ClientBlocksStatus.setStatus('success', 'Ready');
            
            lastSavedContent = { ...editorStore };
            lastPreviewContent = { ...editorStore };
        } catch (error) {
            console.error('Error loading block:', error);
            ClientBlocksStatus.setStatus('error', 'Load failed');
        }
    };

    const saveBlock = async () => {
        try {
            ClientBlocksStatus.setStatus('warning', 'Saving...');
            
            const dataToSave = {
                [currentTab]: editorStore[currentTab]
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
            ClientBlocksStatus.setStatus('warning', 'Saving...');
            
            await $.ajax({
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
            updatePreview();
        } catch (error) {
            console.error('Error saving:', error);
            ClientBlocksStatus.setStatus('error', 'Save failed');
        }
    };

    const updateEditor = () => {
        if (!editor) return;
        const language = ClientBlocksLanguageConfig[currentTab];
        const model = editor.getModel();
        monaco.editor.setModelLanguage(model, language);
        editor.setValue(editorStore[currentTab] || '');
    };

    const updatePreview = _.debounce(() => {
        ClientBlocksPreview.updatePreview(
            editorStore,
            blockData,
            lastPreviewContent,
            ClientBlocksStatus.setStatus
        ).then(context => {
            updateContextEditor(context);
            lastPreviewContent = { ...editorStore };
        });
    }, 1000);

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

    const updateContextEditor = (context) => {
        if (!contextEditor) return;
        contextEditor.setValue(JSON.stringify(context || {}, null, 2));
    };

    return {
        init,
        loadBlock,
        globalSave
    };
})(jQuery);

jQuery(document).ready(function() {
    ClientBlocksEditor.init();
});
