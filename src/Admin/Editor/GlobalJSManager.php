<?php
namespace ClientBlocks\Admin\Editor;

class GlobalJSManager {
    private static $instance = null;
    private $upload_dir;
    private $js_file;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->js_file = $this->upload_dir['basedir'] . '/client-blocks/global/scripts.js';
        
        if (!file_exists(dirname($this->js_file))) {
            wp_mkdir_p(dirname($this->js_file));
        }
        
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    private function get_default_js() {
        return <<<'JS'
document.addEventListener('DOMContentLoaded', function() {
    initializeClientBlocks();
});

function initializeClientBlocks() {
    const blocks = document.querySelectorAll('.wp-block-acf');
    
    blocks.forEach(block => {
        setupBlockEvents(block);
        initializeBlockFeatures(block);
    });
}

function setupBlockEvents(block) {
    block.addEventListener('click', function(e) {
    });
    
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        },
        { threshold: 0.1 }
    );
    
    observer.observe(block);
}

function initializeBlockFeatures(block) {
    initializeAnimations(block);
    initializeLazyLoading(block);
}

function initializeAnimations(block) {
    const animatedElements = block.querySelectorAll('[data-animation]');
    animatedElements.forEach(element => {
        const animation = element.dataset.animation;
        if (animation) {
            element.classList.add(`animate-${animation}`);
        }
    });
}

function initializeLazyLoading(block) {
    const lazyImages = block.querySelectorAll('img[loading="lazy"]');
    lazyImages.forEach(img => {
        img.addEventListener('load', function() {
            this.classList.add('is-loaded');
        });
    });
}

const ClientBlocksUtils = {
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle: (func, limit) => {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    getBreakpoint: () => {
        const width = window.innerWidth;
        if (width < 640) return 'xs';
        if (width < 768) return 'sm';
        if (width < 1024) return 'md';
        if (width < 1280) return 'lg';
        return 'xl';
    }
};
JS;
    }
    
    private function save_default_js() {
        $default_js = $this->get_default_js();
        file_put_contents($this->js_file, $default_js);
        return $default_js;
    }
    
    public function get_js() {
        if (!file_exists($this->js_file) || filesize($this->js_file) == 0) {
            return $this->save_default_js();
        }
        
        return file_get_contents($this->js_file);
    }
    
    public function save_js($js) {
        file_put_contents($this->js_file, $js);
    }
    
    public function register_rest_routes() {
        register_rest_route('client-blocks/v1', '/global-js', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_js_endpoint'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_js_endpoint'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                }
            ]
        ]);
    }
    
    public function get_js_endpoint() {
        return rest_ensure_response([
            'js' => $this->get_js()
        ]);
    }
    
    public function update_js_endpoint($request) {
        $js = $request->get_param('js');
        
        if (!is_string($js)) {
            return new \WP_Error('invalid_js', 'Invalid JavaScript data', ['status' => 400]);
        }
        
        $this->save_js($js);
        return rest_ensure_response([
            'js' => $this->get_js()
        ]);
    }
}
