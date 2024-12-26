window.ClientBlocksGlobalFiles = (function($) {
  let currentFile = {
    css: null,
    js: null
  };
  
  let files = {
    css: [],
    js: []
  };

  const init = async (initialFiles = null) => {
    if (initialFiles) {
      files = {
        css: initialFiles.css || [],
        js: initialFiles.js || []
      };
    } else {
      await Promise.all([
        loadFiles('css'),
        loadFiles('js')
      ]);
    }

    ['css', 'js'].forEach(type => {
      const mainFile = files[type].find(f => f.name === `main.${type}`);
      currentFile[type] = mainFile || files[type][0] || null;
      
      if (currentFile[type]) {
        window.ClientBlocksStore.updateEditorStore(`global-${type}`, currentFile[type].content);
      }
    });

    attachEventListeners();
    setupFileSelectors();
  };

  const loadFiles = async (type) => {
    try {
      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/global-files/${type}`,
        headers: { 'X-WP-Nonce': clientBlocksEditor.nonce }
      });
      
      files[type] = response.files;
      return response;
    } catch (error) {
      console.error(`Error loading ${type} files:`, error);
      return [];
    }
  };

  const createFile = async (type, filename) => {
    try {
      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/global-files/${type}/create`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify({ filename })
      });
      
      files[type] = response.files;
      setupFileSelectors();
      
      const newFile = files[type].find(f => f.name === (filename.endsWith(`.${type}`) ? filename : `${filename}.${type}`));
      if (newFile) {
        currentFile[type] = newFile;
        window.ClientBlocksStore.updateEditorStore(`global-${type}`, newFile.content);
        window.ClientBlocksMonaco.setEditorValue(`global-${type}`, newFile.content);
      }
      
      return response;
    } catch (error) {
      console.error('Error creating file:', error);
      throw error;
    }
  };

  const saveFiles = async (type) => {
    try {
      if (currentFile[type]) {
        const fileIndex = files[type].findIndex(f => f.name === currentFile[type].name);
        if (fileIndex !== -1) {
          const newContent = window.ClientBlocksMonaco.getEditorValue(`global-${type}`);
          files[type][fileIndex].content = newContent;
          window.ClientBlocksStore.updateEditorStore(`global-${type}`, newContent);
        }
      }

      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/global-files/${type}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify(files[type])
      });

      files[type] = response.files;
      return response;
    } catch (error) {
      console.error('Error saving files:', error);
      throw error;
    }
  };

  const setupFileSelectors = () => {
    ['css', 'js'].forEach(type => {
      const selector = createFileSelector(type);
      $(`#global-${type}-selector`).html(selector);
    });
  };

  const createFileSelector = (type) => {
    const selectedFile = currentFile[type]?.name || 'Select a file...';
    const filesList = files[type].map(file => {
      const isSelected = currentFile[type]?.name === file.name;
      return `
        <div class="file-selector-option ${isSelected ? 'selected' : ''}" 
             data-type="${type}" 
             data-filename="${file.name}">
          <ion-icon name="${getFileIcon(file.name)}"></ion-icon>
          <span>${file.name}</span>
        </div>
      `;
    }).join('');

    return `
      <div class="file-selector-container">
        <div class="custom-file-selector">
          <div class="file-selector-trigger" data-type="${type}">
            <span>${selectedFile}</span>
            <ion-icon name="chevron-down-outline"></ion-icon>
          </div>
          <div class="file-selector-dropdown">
            <div class="file-selector-search">
              <input type="text" 
                     placeholder="Search files..." 
                     data-type="${type}"
                     class="file-search-input">
            </div>
            <div class="file-selector-options">
              ${filesList}
            </div>
          </div>
        </div>
        <button type="button" class="create-file-button" data-type="${type}">
          <ion-icon name="add-outline"></ion-icon>
        </button>
      </div>
    `;
  };

  const getFileIcon = (filename) => {
    if (filename.endsWith('.css')) {
      return 'logo-css3';
    } else if (filename.endsWith('.js')) {
      return 'logo-javascript';
    }
    return 'document-outline';
  };

  const attachEventListeners = () => {
    $(document).off('click', '.file-selector-trigger');
    $(document).off('click', '.file-selector-option');
    $(document).off('click', '.create-file-button');
    $(document).off('input', '.file-search-input');
    
    $(document).on('click', '.file-selector-trigger', toggleDropdown);
    $(document).on('click', '.file-selector-option', handleFileSelect);
    $(document).on('click', '.create-file-button', handleCreateFile);
    $(document).on('input', '.file-search-input', handleFileSearch);
    
    $(document).on('click', function(e) {
      if (!$(e.target).closest('.custom-file-selector').length) {
        $('.file-selector-dropdown').removeClass('active');
        $('.file-selector-trigger').removeClass('active');
      }
    });
    
    $('.tab-button').on('click', function() {
      const tab = $(this).data('tab');
      updateFileSelectorVisibility(tab);
      if (tab === 'global-css' || tab === 'global-js') {
        const type = tab.replace('global-', '');
        if (currentFile[type]) {
          window.ClientBlocksMonaco.setEditorValue(tab, currentFile[type].content);
        }
      }
    });

    const activeTab = $('.tab-button.active').data('tab');
    updateFileSelectorVisibility(activeTab);
  };

  const toggleDropdown = function(e) {
    e.stopPropagation();
    const $trigger = $(this);
    const $dropdown = $trigger.next('.file-selector-dropdown');
    
    $('.file-selector-dropdown').not($dropdown).removeClass('active');
    $('.file-selector-trigger').not($trigger).removeClass('active');
    
    $trigger.toggleClass('active');
    $dropdown.toggleClass('active');
    
    if ($dropdown.hasClass('active')) {
      $dropdown.find('.file-search-input').focus();
    }
  };

  const handleFileSelect = function() {
    const type = $(this).data('type');
    const filename = $(this).data('filename');
    const file = files[type].find(f => f.name === filename);
    
    if (file) {
      currentFile[type] = file;
      window.ClientBlocksStore.updateEditorStore(`global-${type}`, file.content);
      window.ClientBlocksMonaco.setEditorValue(`global-${type}`, file.content);
      
      $(`.file-selector-option[data-type="${type}"]`).removeClass('selected');
      $(this).addClass('selected');
      $(`.file-selector-trigger[data-type="${type}"] span`).text(filename);
      
      $('.file-selector-dropdown').removeClass('active');
      $('.file-selector-trigger').removeClass('active');
    }
  };

  const handleCreateFile = async function() {
    const type = $(this).data('type');
    const filename = prompt(`Enter name for new ${type.toUpperCase()} file:`);
    
    if (filename) {
      try {
        await createFile(type, filename);
      } catch (error) {
        alert('Error creating file: ' + error.message);
      }
    }
  };

  const handleFileSearch = function() {
    const type = $(this).data('type');
    const searchTerm = $(this).val().toLowerCase();
    
    $(`.file-selector-option[data-type="${type}"]`).each(function() {
      const filename = $(this).data('filename').toLowerCase();
      $(this).toggle(filename.includes(searchTerm));
    });
  };

  const updateFileSelectorVisibility = (activeTab) => {
    $('.file-selector-wrapper').removeClass('active');
    if (activeTab === 'global-css' || activeTab === 'global-js') {
      $(`#${activeTab}-selector`).addClass('active');
    }
  };

  const getCurrentFiles = () => files;
  const getCurrentFile = (type) => currentFile[type];
  
  const saveCurrentFile = () => {
    const activeTab = $('.tab-button.active').data('tab');
    if (activeTab === 'global-css' || activeTab === 'global-js') {
      const type = activeTab.replace('global-', '');
      return saveFiles(type);
    }
  };

  return {
    init,
    getCurrentFiles,
    getCurrentFile,
    saveCurrentFile
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksGlobalFiles.init();
});
