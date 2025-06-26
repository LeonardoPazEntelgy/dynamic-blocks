<?php
class DBB_Blocks_Register {
    private $allowed_blocks = [];

    public function __construct() {
        add_action('init', [$this, 'register_blocks']);
        add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 10, 2);
        if (!file_exists(DBB_USER_BLOCKS_DIR)) {
            wp_mkdir_p(DBB_USER_BLOCKS_DIR);
        }
    }

    public function register_blocks() {
        // $blocks_dir = DBB_PLUGIN_DIR . 'blocks/';
        $blocks_dir = DBB_USER_BLOCKS_DIR;
        if (!is_dir($blocks_dir)) return;

        foreach (scandir($blocks_dir) as $folder) {
            if ($folder === '.' || $folder === '..') continue;

            $block_dir = $blocks_dir . $folder . '/';
            $block_json_path = $block_dir . 'block.json';
            $index_js_path = $block_dir . 'index.js';

            if (!file_exists($block_json_path)) continue;

            $block_data = json_decode(file_get_contents($block_json_path), true);
            $block_name = $block_data['name'];
            $acf_block_name = str_replace('/', '-', $block_name);
            $is_child = !empty($block_data['dbbIsChild']);
            $is_innerblock = file_exists($index_js_path) && strpos(file_get_contents($index_js_path), 'InnerBlocks') !== false;

            $handle = 'dbb-' . $folder;
            if (file_exists($index_js_path)) {
                wp_register_script(
                    $handle,
                    // DBB_PLUGIN_URL . "blocks/$folder/index.js",
                    DBB_USER_BLOCKS_URL . "$folder/index.js",
                    ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'],
                    filemtime($index_js_path),
                    true
                );
            }

            if ($is_innerblock || $is_child) {
                $this->allowed_blocks[] = $block_name;
            }

            if ($is_innerblock || $is_child) {
                register_block_type_from_metadata($block_dir, [
                    'editor_script' => $handle,
                ]);
            } else {
                $render_template_path = $block_dir . 'render.php';
                if (!file_exists($render_template_path)) {
                    file_put_contents($render_template_path, "<?php\n// Render dinámico generado automáticamente\n");
                }

                if (function_exists('acf_register_block_type')) {
                    acf_register_block_type([
                        'name' => $acf_block_name,
                        'title' => $block_data['title'] ?? ucfirst($folder),
                        'category' => $block_data['category'] ?? 'widgets',
                        'icon' => $block_data['icon'] ?? 'block-default',
                        'mode' => 'edit',
                        'supports' => $block_data['supports'] ?? [],
                        'post_types' => ['page'],
                        'render_template' => "$block_dir/render.php",
                        'enqueue_assets' => function () use ($handle) {
                            if (!empty($handle)) wp_enqueue_script($handle);
                        }
                    ]);
                }
            }
        }
    }

    public function filter_allowed_blocks($allowed_blocks, $editor_context) {
        if (!is_array($allowed_blocks)) {
            $allowed_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
            $allowed_blocks = array_keys($allowed_blocks);
        }

        return array_filter($allowed_blocks, function ($block_name) {
            if (!str_starts_with($block_name, 'dbb/')) return true;
            return in_array($block_name, $this->allowed_blocks, true);
        });
    }
}