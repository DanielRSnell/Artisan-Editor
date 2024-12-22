<!DOCTYPE html>
<html <?php language_attributes();?>>
<head>
    <meta charset="<?php bloginfo('charset');?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $block_id = isset($_GET['block_id']) ? intval($_GET['block_id']) : 0;
    $block = get_post($block_id);
    $block_title = $block ? esc_html($block->post_title) : 'Block Editor';
    ?>
    <title><?php echo $block_title; ?> - Block Editor</title>
    <?php wp_head();?>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
</head>
<body class="client-blocks-editor-page">
    <div class="client-blocks-editor">
        <div class="editor-header">
            <div class="editor-logo">
                <ion-icon name="cube-outline"></ion-icon>
                <span><?php echo $block_title; ?></span>
            </div>
            <div class="breakpoint-controls">
                <?php
                $breakpoints = \ClientBlocks\Admin\Editor\BreakpointManager::instance()->get_breakpoints();
                foreach ($breakpoints as $breakpoint):
                    $title = "{$breakpoint['name']} ({$breakpoint['width']}px)";
                    ?>
                    <button type="button"
                            class="breakpoint-button"
                            data-breakpoint="<?php echo esc_attr($breakpoint['id']); ?>"
                            title="<?php echo esc_attr($title); ?>">
                        <ion-icon name="<?php echo esc_attr($breakpoint['icon']); ?>"></ion-icon>
                    </button>
                <?php endforeach;?>
                <button type="button" class="breakpoint-settings" title="Breakpoint Settings">
                    <ion-icon name="settings-outline"></ion-icon>
                </button>
            </div>
        </div>

        <div class="editor-container">
            <div class="editor-sidebar">
                <div class="editor-tabs">
                    <button type="button" class="tab-button active" data-tab="php" data-title="PHP Logic">
                        <ion-icon name="code-slash-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="template" data-title="HTML Template">
                        <ion-icon name="document-text-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="css" data-title="CSS Styles">
                        <ion-icon name="brush-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="global-css" data-title="Global CSS">
                        <ion-icon name="globe-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="js" data-title="JavaScript">
                        <ion-icon name="logo-javascript"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="context" data-title="Block Context">
                        <ion-icon name="eye-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="acf" data-title="ACF Fields">
                        <ion-icon name="construct-outline"></ion-icon>
                    </button>
                    <button type="button" class="tab-button" data-tab="settings" data-title="Editor Settings">
                        <ion-icon name="settings-outline"></ion-icon>
                    </button>
                </div>
            </div>

            <div class="editor-main">
                <div class="editor-pane">
                    <div class="editor-top-bar">
                        <div class="editor-top-bar-title">PHP Logic</div>
                        <div class="editor-top-bar-actions">
                            <div class="editor-status">
                                <div class="editor-status-indicator"></div>
                                <span class="editor-status-text">Ready</span>
                            </div>
                            <button type="button" class="editor-action-button" title="Format Code">
                                <ion-icon name="code-outline"></ion-icon>
                            </button>
                            <button type="button" class="editor-action-button" title="Copy Code">
                                <ion-icon name="copy-outline"></ion-icon>
                            </button>
                            <button type="button" class="editor-action-button" id="save-block" title="Save Changes">
                                <ion-icon name="save-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                    <div class="editor-container-wrapper">
                        <div id="monaco-editor"></div>
                        <div id="context-editor"></div>
                        <div id="acf-form-container">
                            <?php 
                            if (function_exists('acf_form')) {
                                acf_form([
                                    'post_id' => $block_id,
                                    'form' => true,
                                    'return' => false,
                                    'submit_value' => 'Update Fields'
                                ]);
                            }
                            ?>
                        </div>
                        <div id="settings-container"></div>
                    </div>
                </div>
                <div class="editor-preview">
                    <div class="preview-container">
                        <div class="preview-frame-container" data-breakpoint="xl">
                            <iframe id="preview-frame" src="<?php echo esc_url(get_permalink($block_id)); ?>"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php wp_footer();?>
</body>
</html>
