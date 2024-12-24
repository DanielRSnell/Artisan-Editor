window.ClientBlocksSidebar = (function($) {
  function createTabButton(tab) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `tab-button${tab.defaultActive ? ' active' : ''}`;
    button.dataset.tab = tab.id;
    button.dataset.title = tab.title;
    if (tab.language) {
      button.dataset.language = tab.language;
    }
    if (tab.editor) {
      button.dataset.editor = tab.editor;
    }

    const icon = document.createElement('ion-icon');
    icon.name = tab.icon;
    button.appendChild(icon);

    return button;
  }

  function createDivider() {
    const divider = document.createElement('div');
    divider.className = 'editor-tabs-divider';
    return divider;
  }

  function initializeSidebar() {
    const sidebar = document.getElementById('editor-sidebar');
    if (!sidebar) return;

    const tabsContainer = document.createElement('div');
    tabsContainer.className = 'editor-tabs';

    ClientBlocksTabs.mainTabs.forEach(tab => {
      tabsContainer.appendChild(createTabButton(tab));
    });

    tabsContainer.appendChild(createDivider());

    ClientBlocksTabs.utilityTabs.forEach(tab => {
      tabsContainer.appendChild(createTabButton(tab));
    });

    const bottomContainer = document.createElement('div');
    bottomContainer.className = 'editor-tabs-bottom';

    ClientBlocksTabs.bottomTabs.forEach(tab => {
      bottomContainer.appendChild(createTabButton(tab));
    });

    sidebar.appendChild(tabsContainer);
    sidebar.appendChild(bottomContainer);

    initializeTabHandlers();
  }

  function initializeTabHandlers() {
    $(document).on('click', '.tab-button', function() {
      const $tab = $(this);
      const tabId = $tab.data('tab');
      const editor = $tab.data('editor') || 'monaco';
      const language = $tab.data('language');

      $('.tab-button').removeClass('active');
      $tab.addClass('active');

      $('.editor-top-bar-title').text($tab.data('title'));

      $('#monaco-editor, #context-editor, #acf-form-container, #settings-container').hide();

      switch(editor) {
        case 'monaco':
          $('#monaco-editor').show();
          if (window.editor && language) {
            monaco.editor.setModelLanguage(editor.getModel(), language);
            editor.setValue(editorStore[tabId] || '');
          }
          break;
        case 'form':
          $('#acf-form-container').show();
          break;
        case 'custom':
          $('#settings-container').show();
          break;
      }
    });
  }

  return {
    init: initializeSidebar
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksSidebar.init();
});
