<?php
class DBB_REST_API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('dynamic-blocks/v1', '/create', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'check_admin_permissions'],
            'callback' => [$this, 'create_block'],
        ]);
        
        register_rest_route('dynamic-blocks/v1', '/list', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'check_admin_permissions'],
            'callback' => [$this, 'list_blocks'],
        ]);

        // Exportar bloques
        register_rest_route( 'dynamic-blocks/v1', '/export-zip', [
            'methods'  => 'GET',
            'callback' => [ $this, 'export_blocks_zip' ],
            // 'permission_callback' => function () {
            //     return current_user_can('manage_options'); // solo admin
            // }
            'permission_callback' => '__return_true',
        ]);

        // Importar bloques
        register_rest_route('dynamic-blocks/v1', '/import-zip', [
            'methods' => 'POST',
            'callback' => [ $this, 'import_blocks_zip' ],
            // 'permission_callback' => function () {
            //     return current_user_can('manage_options');
            // }
            'permission_callback' => '__return_true',
        ]);      

        register_rest_route('dynamic-blocks/v1', '/delete/(?P<name>[a-z0-9\-]+)', [
            'methods' => 'DELETE',
            'permission_callback' => [$this, 'check_admin_permissions'],
            'callback' => [$this, 'delete_block'],
        ]);

        register_rest_route('dynamic-blocks/v1', '/get/(?P<name>[a-z0-9\-]+)', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'check_admin_permissions'],
            'callback' => [$this, 'get_block'],
        ]);

        register_rest_route('dynamic-blocks/v1', '/update/(?P<name>[a-z0-9\-]+)', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'check_admin_permissions'],
            'callback' => [$this, 'update_block'],
        ]);
        
        register_rest_route('dynamic-blocks/v1', '/page-blocks/(?P<id>[a-zA-Z0-9-_]+)', [
            'methods'  => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'get_page_blocks'],
        ]);
    }   

    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    public function create_block($request) {
        $name = sanitize_title($request['blockName']);
        $title = sanitize_text_field($request['blockTitle']);
        $category = sanitize_text_field($request['category']);
        $icon = dbb_decode_and_sanitize_svg_icon($request['icon']);
        $template = sanitize_text_field($request['template'] ?? 'default');
        $isChildBlock = !empty($request['isChildBlock']);
        // $dir = DBB_PLUGIN_DIR . "blocks/$name/";
        $dir = DBB_USER_BLOCKS_DIR . "$name/";

        if (file_exists($dir)) {
            return new WP_Error('block_exists', 'El bloque ya existe.', ['status' => 400]);
        }

        wp_mkdir_p($dir);

        $block_json = [
            'apiVersion' => 2,
            'name' => "dbb/$name",
            'title' => $title,
            'category' => $category,
            'icon' => $icon,
            'dbbIsChild' => $isChildBlock,
            'supports' => [
                'customFields' => true,
                'align' => true,
                'anchor' => true,
                'color' => [
                    'background' => true,
                    'text' => true
                ],
                'spacing' => [
                    'margin' => true,
                    'padding' => true
                ],
                'typography' => [
                    'fontSize' => true
                ]
            ],
            'editorScript' => 'file:./index.js'
        ];

        if ($template === 'innerblocks') {
            $index_js = <<<JS
            (function(blocks, blockEditor, element) {
                var el = element.createElement;
                var useBlockProps = blockEditor.useBlockProps;
                var InnerBlocks = blockEditor.InnerBlocks;

                blocks.registerBlockType('dbb/{$name}', {
                    title: '{$title}',
                    icon: (() => {
                    const raw = `{$icon}`;
                    try {
                        const decoded = raw.startsWith('data:image/svg+xml;base64,')
                        ? atob(raw.replace(/^data:image\\/svg\\+xml;base64,/, ''))
                        : raw;
                        if (decoded.startsWith('<svg')) {
                        return {
                            src: () => el('span', { dangerouslySetInnerHTML: { __html: decoded } })
                        };
                        }
                    } catch (e) {
                        console.warn('Error decoding SVG:', e);
                    }
                    return raw;
                    })(),
                    category: '{$category}',
                    supports: {
                        inserter: true,
                        reusable: true
                    },
                    edit: function(props) {
                        const blockProps = useBlockProps();
                        return el('div', blockProps, el(InnerBlocks, {
                            template: [],
                            templateLock: false
                        }));
                    },
                    save: function() {
                        const blockProps = useBlockProps.save();
                        return el('div', blockProps, el(InnerBlocks.Content));
                    }
                });
            })(window.wp.blocks, window.wp.blockEditor, window.wp.element);
            JS;
        } else {
            $index_js = <<<JS
            (function(blocks, blockEditor, element) {
                var el = element.createElement;
                var useBlockProps = blockEditor.useBlockProps;

                blocks.registerBlockType('dbb/{$name}', {
                    title: '{$title}',
                    icon: (() => {
                    const raw = `{$icon}`;
                    try {
                        const decoded = raw.startsWith('data:image/svg+xml;base64,')
                        ? atob(raw.replace(/^data:image\\/svg\\+xml;base64,/, ''))
                        : raw;
                        if (decoded.startsWith('<svg')) {
                        return {
                            src: () => el('span', { dangerouslySetInnerHTML: { __html: decoded } })
                        };
                        }
                    } catch (e) {
                        console.warn('Error decoding SVG:', e);
                    }
                    return raw;
                    })(),
                    category: '{$category}',
                    edit: function() {
                        const blockProps = useBlockProps();
                        return el('div', blockProps,
                            el('p', {}, 'Edita los campos del bloque en el panel derecho (ACF).')
                        );
                    },
                    save: function() {
                        const blockProps = useBlockProps.save();
                        return el('div', blockProps, null);
                    }
                });
            })(window.wp.blocks, window.wp.blockEditor, window.wp.element);
            JS;
        }

        file_put_contents($dir . 'block.json', json_encode($block_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($dir . 'index.js', $index_js);
        dbb_generate_render_template($dir, []);
        return ['success' => true, 'name' => $name];
    }

    public function list_blocks() {
        $blocks = [];
        // $dir = DBB_PLUGIN_DIR . 'blocks/';
        $dir = DBB_USER_BLOCKS_DIR;
        if (!is_dir($dir)) return $blocks;

        foreach (scandir($dir) as $folder) {
            if ($folder === '.' || $folder === '..') continue;

            $block_json = $dir . $folder . '/block.json';
            if (!file_exists($block_json)) continue;

            $data = json_decode(file_get_contents($block_json), true);
            $blocks[] = [
                'name' => $data['name'],
                'title' => $data['title'] ?? '',
                'category' => $data['category'] ?? '',
                'icon' => $data['icon'] ?? '',
            ];
        }

        return $blocks;
    }

    public function delete_block($request) {
        $name = sanitize_title($request['name']);
        // $path = DBB_PLUGIN_DIR . "blocks/$name";
        $path = DBB_USER_BLOCKS_DIR . "$name";

        if (!is_dir($path)) {
            return new WP_Error('not_found', 'Bloque no encontrado.', ['status' => 404]);
        }

        array_map('unlink', glob("$path/*.*"));
        rmdir($path);

        return ['deleted' => true, 'name' => $name];
    }

    public function get_block($request) {
        $name = sanitize_title($request['name']);
        // $dir = DBB_PLUGIN_DIR . "blocks/$name/";
        $dir = DBB_USER_BLOCKS_DIR . "$name/";

        $block_json_path = $dir . 'block.json';
        $index_js_path = $dir . 'index.js';

        if (!file_exists($block_json_path)) {
            return new WP_Error('not_found', 'Bloque no encontrado.', ['status' => 404]);
        }

        return [
            'blockJson' => json_decode(file_get_contents($block_json_path), true),
            'indexJs' => file_exists($index_js_path) ? file_get_contents($index_js_path) : ''
        ];
    }

    public function update_block($request) {
        $name = sanitize_title($request['name']);
        // $dir = DBB_PLUGIN_DIR . "blocks/$name/";
        $dir = DBB_USER_BLOCKS_DIR . "$name/";
        $block_json_path = $dir . 'block.json';
        $index_js_path = $dir . 'index.js';

        if (!file_exists($block_json_path)) {
            return new WP_Error('not_found', 'Bloque no encontrado.', ['status' => 404]);
        }

        $title = sanitize_text_field($request['title']);
        $category = sanitize_text_field($request['category']);
        $icon = dbb_decode_and_sanitize_svg_icon($request['icon']);
        $template = sanitize_text_field($request['template'] ?? 'default');
        $isChildBlock = !empty($request['isChildBlock']);

        $block_json = json_decode(file_get_contents($block_json_path), true);
        $block_json['title'] = $title;
        $block_json['category'] = $category;
        $block_json['icon'] = $icon;
        $block_json['dbbIsChild'] = $isChildBlock;

        file_put_contents($block_json_path, json_encode($block_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (!empty($request['indexJs'])) {
            file_put_contents($index_js_path, $request['indexJs']);
        }

        return ['success' => true];
    }
    
    public function get_page_blocks($request) {
        $param = $request['id'];

        if (is_numeric($param)) {
            $post = get_post(intval($param));
        } else {
            $post = get_page_by_path(sanitize_title($param), OBJECT, 'page');
        }

        if (!$post || $post->post_type !== 'page') {
            return new WP_Error('not_found', 'Página no encontrada', ['status' => 404]);
        }

        $raw_blocks = parse_blocks($post->post_content);
        $processed_blocks = array_map([$this, 'process_block'], $raw_blocks);

        return [
            'post_id' => $post->ID,
            'title' => get_the_title($post->ID),
            'blocks' => $processed_blocks,
        ];
    }

    public function process_block($block) {
        if (!isset($block['blockName'])) return null;

        $is_acf_block = strpos($block['blockName'], 'acf/dbb-') === 0;
        $attrs = $block['attrs'] ?? [];
        $data  = [];

        if ($is_acf_block && isset($attrs['data']) && is_array($attrs['data'])) {
            $raw = $attrs['data'];
            $grouped = [];
            
            foreach ($raw as $key => $value) {
                if (strpos($key, '_') === 0) continue;

                if (preg_match('/^([a-zA-Z0-9]+)_(\d+)_([a-zA-Z0-9_-]+)$/', $key, $matches)) {
                    [$full, $parent, $index, $child] = $matches;
                    $index = intval($index);

                    if (!isset($grouped[$parent])) {
                        $grouped[$parent] = [];
                    }

                    if (!isset($grouped[$parent][$index])) {
                        $grouped[$parent][$index] = [];
                    }

                    $grouped[$parent][$index][$child] = dbb_format_value($child, $value);
                }

                elseif (preg_match('/^([a-zA-Z0-9]+)_([a-zA-Z0-9_-]+)$/', $key, $matches)) {
                    [$full, $parent, $child] = $matches;

                    if (isset($raw[$parent]) && is_array($raw[$parent])) continue;

                    if (!isset($grouped[$parent]) || !is_array($grouped[$parent])) {
                        $grouped[$parent] = [];
                    }

                    if (!isset($grouped[$parent]['__meta'])) {
                        $grouped[$parent]['__meta'] = [];
                    }

                    $grouped[$parent]['__meta'][$child] = dbb_format_value($child, $value);
                }

                else {
                    $data[$key] = dbb_format_value($key, $value);
                }
            }

            // 🔁 Convertir todos los grupos en estructura final
            foreach ($grouped as $groupKey => $groupVal) {
                $final = [];

                if (isset($groupVal['__meta'])) {
                    $final = $groupVal['__meta'];
                    unset($groupVal['__meta']);
                }

                if (!empty($groupVal)) {
                    ksort($groupVal);
                    $final['items'] = array_values($groupVal);
                }

                $grouped[$groupKey] = $final;
            }

            $data = array_merge($data, $grouped);


        }

        $inner = [];
        if (!empty($block['innerBlocks'])) {
            $inner = array_map([$this, 'process_block'], $block['innerBlocks']);
            $inner = array_filter($inner);
        }

        $anchor = $attrs['anchor'] ?? '';
        $unique_id = $anchor !== '' ? $anchor : dbb_generate_uuid();

        return [
            'block' => $block['blockName'],
            'id' => $unique_id,
            'attributes' => [
                'name'   => $attrs['name'] ?? '',
                'anchor' => $anchor,
                'mode'   => $attrs['mode'] ?? 'preview',
                'data'   => $data,
            ],
            'innerBlocks' => $inner,
        ];
    }

    // EXPORTA todos los bloques guardados en el sistema
    public function export_blocks_zip() {
        $upload_dir = wp_upload_dir();
        $blocks_dir = trailingslashit($upload_dir['basedir']) . 'dbb-blocks/';
        $zip_file = $upload_dir['basedir'] . '/dbb-export-' . date('Ymd-His') . '.zip';

        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_missing', 'ZipArchive no disponible', ['status' => 500]);
        }

        if (!is_dir($blocks_dir)) {
            return new WP_Error('no_blocks', 'No hay bloques para exportar', ['status' => 404]);
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return new WP_Error('zip_error', 'No se pudo crear el ZIP', ['status' => 500]);
        }

        // 🧠 Recorremos cada carpeta de bloque
        $block_dirs = glob($blocks_dir . '*', GLOB_ONLYDIR);
        foreach ($block_dirs as $block_dir) {
            $block_name = basename($block_dir);
            $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($block_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getRealPath();
                    $relativePath = $block_name . '/' . substr($filePath, strlen($block_dir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();

        // 🧾 Forzar headers para que el navegador lo descargue correctamente
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);

        unlink($zip_file); // limpieza
        exit;
    }


    // IMPORTA un conjunto de bloques
    public function import_blocks_zip($request) {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'Archivo no recibido correctamente.', ['status' => 400]);
        }

        $zip_tmp_path = $_FILES['file']['tmp_name'];

        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_missing', 'ZipArchive no está disponible.', ['status' => 500]);
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_tmp_path) !== true) {
            return new WP_Error('invalid_zip', 'No se pudo abrir el archivo ZIP.', ['status' => 400]);
        }

        $upload_dir = wp_upload_dir();
        $target_dir = trailingslashit($upload_dir['basedir']) . 'dbb-blocks/';

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Extraer a un directorio temporal para inspección
        $tmp_extract_dir = $upload_dir['basedir'] . '/dbb-tmp-' . time() . '/';
        mkdir($tmp_extract_dir, 0755, true);
        $zip->extractTo($tmp_extract_dir);
        $zip->close();

        // Reubicar carpetas válidas
        $imported = [];
        foreach (scandir($tmp_extract_dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $block_path = $tmp_extract_dir . $entry;
            if (is_dir($block_path)) {
            $dest = $target_dir . $entry;
            if (file_exists($dest)) {
                // Sobrescribir bloque si ya existe
                $this->rrmdir($dest);
            }
            rename($block_path, $dest);
            $imported[] = $entry;
            }
        }

        // Limpiar
        $this->rrmdir($tmp_extract_dir);

        return new WP_REST_Response([
            'success' => true,
            'imported' => $imported
        ], 200);
        }

        private function rrmdir($dir) {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}