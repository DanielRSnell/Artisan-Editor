<?php
namespace ClientBlocks\Admin\Editor;

class GlobalCSSManager {
    private static $instance = null;
    private $upload_dir;
    private $css_file;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->css_file = $this->upload_dir['basedir'] . '/client-blocks/global/raw.css';
        
        if (!file_exists(dirname($this->css_file))) {
            wp_mkdir_p(dirname($this->css_file));
        }
        
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    private function get_default_css() {
        return <<<'CSS'
/* Global Client Blocks CSS */
.client-block {
    margin: 2rem 0;
}

/* Common utility classes */
.block-padding {
    padding: 2rem;
}

.block-margin {
    margin: 2rem 0;
}

.block-shadow {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.block-border {
    border: 1px solid #eee;
    border-radius: 4px;
}

/* Responsive utilities */
@media (max-width: 768px) {
    .hide-mobile {
        display: none !important;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .hide-tablet {
        display: none !important;
    }
}

@media (min-width: 1025px) {
    .hide-desktop {
        display: none !important;
    }
}

/* Common animations */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Typography utilities */
.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

/* Flexbox utilities */
.flex { display: flex; }
.flex-col { flex-direction: column; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.justify-between { justify-content: space-between; }
.flex-wrap { flex-wrap: wrap; }

/* Grid utilities */
.grid { display: grid; }
.gap-4 { gap: 1rem; }
.gap-8 { gap: 2rem; }

/* Common grid layouts */
.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
.grid-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 768px) {
    .grid-2, .grid-3, .grid-4 {
        grid-template-columns: 1fr;
    }
}
CSS;
    }
    
    private function save_default_css() {
        $default_css = $this->get_default_css();
        file_put_contents($this->css_file, $default_css);
        return $default_css;
    }
    
    public function get_css() {
        if (!file_exists($this->css_file) || filesize($this->css_file) == 0) {
            return $this->save_default_css();
        }
        
        return file_get_contents($this->css_file);
    }
    
    public function save_css($css) {
        file_put_contents($this->css_file, $css);
    }
    
    public function register_rest_routes() {
        register_rest_route('client-blocks/v1', '/global-css', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_css_endpoint'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_css_endpoint'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                }
            ]
        ]);
    }
    
    public function get_css_endpoint() {
        return rest_ensure_response([
            'css' => $this->get_css()
        ]);
    }
    
    public function update_css_endpoint($request) {
        $css = $request->get_param('css');
        
        if (!is_string($css)) {
            return new \WP_Error('invalid_css', 'Invalid CSS data', ['status' => 400]);
        }
        
        $this->save_css($css);
        return rest_ensure_response([
            'css' => $this->get_css()
        ]);
    }
}
