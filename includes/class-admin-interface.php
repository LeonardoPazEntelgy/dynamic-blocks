<?php
class DBB_Admin_Interface {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Dynamic Blocks',
            'Dynamic Blocks',
            'manage_options',
            'dynamic-blocks',
            [$this, 'render_admin_page'],
            'dashicons-screenoptions'
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><div id="dbb-admin-root"></div></div>';
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_dynamic-blocks') return;

        // Bootstrap 5 desde CDN
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
        );
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [],
            null,
            true
        );

        // Tu UI principal
        wp_enqueue_script(
            'dbb-admin-ui',
            DBB_PLUGIN_URL . 'admin/admin-ui.js',
            ['wp-element', 'wp-components', 'wp-api-fetch'],
            filemtime(DBB_PLUGIN_DIR . 'admin/admin-ui.js'),
            true
        );

        wp_enqueue_style('wp-components');
        wp_enqueue_style(
            'dbb-admin-ui',
            DBB_PLUGIN_URL . 'admin/admin-ui.css',
            [],
            filemtime(DBB_PLUGIN_DIR . 'admin/admin-ui.css')
        );
    }


    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'dbb-admin-ui',
            DBB_PLUGIN_URL . 'admin/admin-ui.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            filemtime(DBB_PLUGIN_DIR . 'admin/admin-ui.js')
        );
    }
}