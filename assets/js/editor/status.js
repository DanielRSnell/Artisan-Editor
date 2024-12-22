window.ClientBlocksStatus = (function($) {
  return {
    setStatus(status, message) {
      const $indicator = $(ClientBlocksElements.statusIndicator);
      const $text = $(ClientBlocksElements.statusText);
      
      $indicator.removeClass('success error warning');
      
      switch(status) {
        case 'success':
          $indicator.addClass('success');
          break;
        case 'error':
          $indicator.addClass('error');
          break;
        case 'warning':
          $indicator.addClass('warning');
          break;
      }
      
      $text.text(message);
    }
  };
})(jQuery);
