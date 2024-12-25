window.ClientBlocksPreviewLauncher = (function($) {
  let isDropdownOpen = false;

  const init = () => {
    attachEvents();
    updateCurrentContextLabel();
  };

  const attachEvents = () => {
    $('.preview-context-button').on('click', toggleContextDropdown);
    $(document).on('click', handleOutsideClick);
    $(window).on('keydown', handleEscapeKey);
  };

  const toggleContextDropdown = (e) => {
    e.stopPropagation();
    isDropdownOpen ? closeContextDropdown() : openContextDropdown();
  };

  const openContextDropdown = () => {
    const $button = $('.preview-context-button');
    const $container = $('#preview-context-container');
    
    const buttonRect = $button[0].getBoundingClientRect();
    
    $container.css({
      top: `${buttonRect.bottom + 5}px`,
      left: `${buttonRect.left}px`
    }).addClass('active');

    isDropdownOpen = true;
  };

  const closeContextDropdown = () => {
    $('#preview-context-container').removeClass('active');
    isDropdownOpen = false;
  };

  const handleOutsideClick = (e) => {
    if (isDropdownOpen && 
        !$(e.target).closest('.preview-context-button, #preview-context-container').length) {
      closeContextDropdown();
    }
  };

  const handleEscapeKey = (e) => {
    if (e.key === 'Escape' && isDropdownOpen) {
      closeContextDropdown();
    }
  };

  const updateCurrentContextLabel = () => {
    const context = window.ClientBlocksPreviewContext?.getCurrentContext();
    if (context) {
      $('.current-context').text(getContextLabel(context));
    }
  };

  const getContextLabel = (context) => {
    switch (context.type) {
      case 'archive':
        return `${context.post_type} Archive`;
      case 'single':
        return context.label || 'Single Post';
      case 'taxonomy':
        return `${context.taxonomy}: ${context.label}`;
      case 'wc_shop':
        return 'Shop';
      case 'wc_product':
        return context.label || 'Product';
      default:
        return context.label || context.type;
    }
  };

  return {
    init,
    updateCurrentContextLabel
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksPreviewLauncher.init();
});
