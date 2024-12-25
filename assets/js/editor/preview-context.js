window.ClientBlocksPreviewContext = (function($) {
  let contexts = {};
  let currentContext = { type: 'archive', post_type: 'post' };
  let onContextChange = null;

  const init = async () => {
    try {
      const response = await fetch(`${clientBlocksEditor.restUrl}/preview-contexts`, {
        headers: {
          'X-WP-Nonce': clientBlocksEditor.nonce
        }
      });
      contexts = await response.json();
      await loadSavedContext();
      renderContextSelector();
      
      if (window.ClientBlocksPreviewLauncher) {
        window.ClientBlocksPreviewLauncher.updateCurrentContextLabel();
      }
    } catch (error) {
      console.error('Error loading preview contexts:', error);
    }
  };

  const setContextChangeHandler = (handler) => {
    onContextChange = handler;
  };

  const renderContextSelector = () => {
    const container = document.getElementById('preview-context-container');
    if (!container) return;

    const html = `
      <div class="preview-context-selector">
        ${renderPostTypeContexts()}
        ${renderTaxonomyContexts()}
        ${contexts.woocommerce ? renderWooCommerceContexts() : ''}
      </div>
    `;

    container.innerHTML = html;
    attachContextEvents();
  };

  const renderPostTypeContexts = () => {
    return Object.entries(contexts.post_types).map(([type, data]) => `
      <div class="context-group">
        <h3>${data.label}</h3>
        <div class="context-items">
          ${data.items.map(item => `
            <div class="context-item ${isCurrentContext(item) ? 'active' : ''}"
                 data-context='${JSON.stringify(item)}'>
              <ion-icon name="${getIconForContext(item)}"></ion-icon>
              <span>${item.label}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `).join('');
  };

  const renderTaxonomyContexts = () => {
    return Object.entries(contexts.taxonomies).map(([tax, data]) => `
      <div class="context-group">
        <h3>${data.label}</h3>
        <div class="context-items">
          ${data.terms.map(term => `
            <div class="context-item ${isCurrentContext(term) ? 'active' : ''}"
                 data-context='${JSON.stringify(term)}'>
              <ion-icon name="pricetag-outline"></ion-icon>
              <span>${term.label} (${term.count})</span>
            </div>
          `).join('')}
        </div>
      </div>
    `).join('');
  };

  const renderWooCommerceContexts = () => {
    const woo = contexts.woocommerce;
    return `
      <div class="context-group">
        <h3>WooCommerce</h3>
        <div class="context-items">
          <div class="context-item ${isCurrentContext(woo.shop) ? 'active' : ''}"
               data-context='${JSON.stringify(woo.shop)}'>
            <ion-icon name="storefront-outline"></ion-icon>
            <span>Shop Page</span>
          </div>
          ${woo.products.map(product => `
            <div class="context-item ${isCurrentContext(product) ? 'active' : ''}"
                 data-context='${JSON.stringify(product)}'>
              <ion-icon name="cube-outline"></ion-icon>
              <span>${product.label}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  };

  const getIconForContext = (context) => {
    switch (context.type) {
      case 'archive':
        return 'albums-outline';
      case 'single':
        return 'document-outline';
      case 'taxonomy':
        return 'pricetag-outline';
      case 'wc_shop':
        return 'storefront-outline';
      case 'wc_product':
        return 'cube-outline';
      default:
        return 'document-outline';
    }
  };

  const isCurrentContext = (context) => {
    if (context.type !== currentContext.type) return false;
    
    switch (context.type) {
      case 'single':
      case 'wc_product':
        return context.post_id === currentContext.post_id;
      case 'archive':
        return context.post_type === currentContext.post_type;
      case 'taxonomy':
        return context.taxonomy === currentContext.taxonomy && 
               context.term_id === currentContext.term_id;
      case 'wc_shop':
        return true;
      default:
        return false;
    }
  };

  const attachContextEvents = () => {
    $('.context-item').on('click', function() {
      $('.context-item').removeClass('active');
      $(this).addClass('active');
      
      const context = JSON.parse($(this).attr('data-context'));
      currentContext = context;
      
      saveContext(context);

      if (window.ClientBlocksEditor) {
        window.ClientBlocksEditor.updatePreview();
      }

      if (window.ClientBlocksPreviewLauncher) {
        window.ClientBlocksPreviewLauncher.updateCurrentContextLabel();
      }

      closeContextDropdown();
    });
  };

  const closeContextDropdown = () => {
    const $container = $('#preview-context-container');
    $container.removeClass('active');
  };

  const loadSavedContext = async () => {
    try {
      const saved = localStorage.getItem('clientBlocksPreviewContext');
      if (saved) {
        currentContext = JSON.parse(saved);
      }
    } catch (e) {
      console.error('Error loading saved context:', e);
    }
  };

  const saveContext = (context) => {
    try {
      localStorage.setItem('clientBlocksPreviewContext', JSON.stringify(context));
    } catch (e) {
      console.error('Error saving context:', e);
    }
  };

  const getCurrentContext = () => {
    return currentContext;
  };

  return {
    init,
    getCurrentContext,
    setContextChangeHandler
  };
})(jQuery);

jQuery(document).ready(function() {
  ClientBlocksPreviewContext.init();
});
