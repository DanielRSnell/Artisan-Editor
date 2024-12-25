const ClientBlocksPreview = (function($) {
  const config = {
    breakpoints: clientBlocksEditor.breakpoints || [],
  };
  
  const elements = {
    container: '.preview-container',
    frameContainer: '.preview-frame-container',
    frame: '#preview-frame',
    breakpointButtons: '.breakpoint-button',
    settingsButton: '.breakpoint-settings'
  };
  
  let currentBreakpoint = null;
  let lastPreviewContent = {};
  let lastPayload = null;

  const handleBreakpointClick = function(e) {
    e.preventDefault();
    const $button = $(this);
    const breakpoint = $button.data('breakpoint');
    
    $(elements.breakpointButtons).removeClass('active');
    $button.addClass('active');
    
    updatePreviewSize(breakpoint);
    currentBreakpoint = breakpoint;
  };
  
  const calculateFrameDimensions = (breakpoint) => {
    const $container = $(elements.container);
    const containerWidth = $container.width();
    const containerHeight = $container.height();
    
    const breakpointData = config.breakpoints.find(b => b.id === breakpoint);
    const targetWidth = breakpointData ? breakpointData.width : 1024;
    
    const scale = containerWidth / targetWidth;
    const frameHeight = Math.ceil(containerHeight / scale);
    
    return {
      width: targetWidth,
      height: frameHeight,
      scale: scale
    };
  };
  
  const updatePreviewSize = (breakpoint) => {
    const $container = $(elements.container);
    const $frameContainer = $(elements.frameContainer);
    
    const { width, height, scale } = calculateFrameDimensions(breakpoint);
    
    $frameContainer.css({
      width: `${width}px`,
      height: `${height}px`,
      transform: `scale(${scale})`,
      transformOrigin: '0 0',
      position: 'absolute',
      left: '0',
      top: '0'
    });
    
    $frameContainer.attr('data-breakpoint', breakpoint);
  };
  
  const handleResize = _.debounce(function() {
    if (currentBreakpoint) {
      updatePreviewSize(currentBreakpoint);
    }
  }, 250);

  const handleSettingsClick = function(e) {
    e.preventDefault();
    if (window.ClientBlocksBreakpoints && typeof window.ClientBlocksBreakpoints.openSettings === 'function') {
      window.ClientBlocksBreakpoints.openSettings();
    }
  };

  const createPayload = (editorStore, blockData, previewContext) => {
    return {
      block_id: blockData.id,
      template: editorStore.template,
      php: editorStore.php,
      css: editorStore['block-css'],
      js: editorStore['block-scripts'],
      json: editorStore['block-json'],
      align: JSON.parse(editorStore['block-json'] || '{}').align || '',
      className: JSON.parse(editorStore['block-json'] || '{}').className || '',
      mode: 'preview',
      supports: JSON.parse(editorStore['block-json'] || '{}').supports || {},
      preview_context: previewContext
    };
  };

  const hasPayloadChanged = (newPayload) => {
    if (!lastPayload) return true;
    return JSON.stringify(newPayload) !== JSON.stringify(lastPayload);
  };

  const updatePreview = async (editorStore, blockData, previewContext) => {
    try {
      const iframe = document.getElementById('preview-frame');
      if (!iframe || !iframe.contentDocument) {
        throw new Error('Preview frame not ready');
      }

      const editorContent = iframe.contentDocument.getElementById('editor-content');
      if (!editorContent) {
        throw new Error('Editor content container not found');
      }

      const payload = createPayload(editorStore, blockData, previewContext);

      if (!hasPayloadChanged(payload)) {
        return lastPreviewContent;
      }

      const response = await $.ajax({
        url: `${clientBlocksEditor.restUrl}/preview`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': clientBlocksEditor.nonce
        },
        data: JSON.stringify(payload)
      });

      editorContent.innerHTML = response.content;
      initializePreviewBlock(iframe, blockData.id);
      
      lastPayload = payload;
      lastPreviewContent = response.context;
      
      return response.context;
    } catch (error) {
      console.error('Error updating preview:', error);
      throw error;
    }
  };

  const initializePreviewBlock = (iframe, blockId) => {
    const script = iframe.contentDocument.createElement('script');
    script.textContent = `
      (function() {
        const block = document.getElementById('block-${blockId}');
        if (!block) return;
        
        const event = new CustomEvent('block-ready', { 
          detail: { 
            blockId: '${blockId}',
            isPreview: true 
          }
        });
        block.dispatchEvent(event);
        
        block.addEventListener('click', function(e) {
          if (e.target.tagName === 'A') {
            e.preventDefault();
          }
        });
      })();
    `;
    iframe.contentDocument.body.appendChild(script);
  };
  
  const init = () => {
    $(elements.container).css('position', 'relative');
    
    $(document).on('click', elements.breakpointButtons, handleBreakpointClick);
    $(document).on('click', elements.settingsButton, handleSettingsClick);
    $(window).on('resize', handleResize);
    
    const $frameContainer = $(elements.frameContainer);
    const initialBreakpoint = $frameContainer.data('breakpoint') || 'xl';
    updatePreviewSize(initialBreakpoint);
    currentBreakpoint = initialBreakpoint;
    
    $(elements.breakpointButtons + `[data-breakpoint="${initialBreakpoint}"]`).addClass('active');
  };

  return {
    init,
    updatePreview,
    updatePreviewSize,
    initializePreviewBlock
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksPreview.init();
});

window.updateWindpress = () => {
  const iframe = document.getElementById('preview-frame');
  if (!iframe.contentWindow) return;
  
  const vfsElement = iframe.contentDocument.querySelector('#windpress\\:vfs');
  if (!vfsElement) return;
  
  try {
    iframe.contentWindow.postMessage({
      source: 'windpress/dashboard',
      target: 'windpress/observer',
      task: 'windpress.code-editor.saved',
      payload: {
        volume: JSON.parse(atob(vfsElement.textContent)),
        comment: 'Activation toggle'
      }
    }, '*');
  } catch (error) {
    console.error('Error triggering Windpress update:', error);
  }
};
