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
        updatePreviewSize
    };
    
})(jQuery);

jQuery(document).ready(function() {
    ClientBlocksPreview.init();
});
