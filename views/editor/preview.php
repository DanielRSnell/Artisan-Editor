<!DOCTYPE html>
<html <?php language_attributes();?>>
<head>
    <meta charset="<?php bloginfo('charset');?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <?php wp_head();?>

    <style>
        html {
            margin-top: 0 !important;
        }
        body {
            margin: 0;
            padding: 0;
        }
        #wpadminbar,
        #windpress-app {
            display: none !important;
        }

        #main {
            padding: 1rem;
        }

        #windpress-app {
            display: none!important;
        }

         body {
        min-height: 100vh;
    }

    .client-blocks-preview-bar {
        display: none!important;
    }

    [data-theme="dark"] {
        --bg-base: #1e1e1e;
        background-color: var(--bg-base);
    }

    [data-theme="light"] {
        --bg-base: #fff;
        background-color: var(--bg-base);
    }

    </style>
</head>
<body <?php body_class();?> data-theme="light">
    <div id="content" class="site-content">
    <main id="main" class="site-main">
    <div id="editor-content">

        <?php
global $post;

// Get custom field client_template
$client_template = get_post_meta($post->ID, '_client_template', true);
echo do_shortcode($client_template);
?>
    </div>
    </main>
    </div>
    <script id="post-context" type="application/json"></script>
    <script id="mock-fields" type="application/json"></script>
    <script id="block-context" type="application/json"></script>
    <script id="timber-context" type="application/json"></script>
    <script id="editor-preview-script">
    window.addEventListener('load', function() {
        window.parent.console.log('🎯 Preview iframe loaded and ready');

        // check for window.windpress
        if (window.windpress) {
            window.parent.console.log('🎯 Windpress detected');
        }

        // Could also signal specific data if needed
        window.parent.iframeLoaded = true;
    });

    // Theme Switcher
    window.parent.childThemeSwitch = function() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    body.setAttribute('data-theme', currentTheme === 'light' ? 'dark' : 'light');
    }

    </script>
    <?php wp_footer();?>
</body>
</html>
