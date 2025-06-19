<?php
/**
 * Plugin Name: Dynamic Blocks Builder
 * Description: Crea bloques dinámicos personalizados desde el administrador.
 * Version: 1.0
 * Author: Entelgy
 */

if (!defined('ABSPATH')) exit;

define('DBB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DBB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Registrar bloques automáticamente
add_action('init', function () {
    $blocks_dir = DBB_PLUGIN_DIR . 'blocks/';
    if (!is_dir($blocks_dir)) return;

    foreach (scandir($blocks_dir) as $folder) {
        if ($folder === '.' || $folder === '..') continue;

        $block_dir = $blocks_dir . $folder . '/';
        $block_json_path = $block_dir . 'block.json';
        $index_js_path = $block_dir . 'index.js';

        if (!file_exists($block_json_path)) continue;

        $block_data = json_decode(file_get_contents($block_json_path), true);
        $block_name = $block_data['name']; // ej. dbb/banner
        $acf_block_name = str_replace('/', '-', $block_name); // ej. dbb-banner
        $is_child = !empty($block_data['dbbIsChild']);
        $is_innerblock = strpos(file_get_contents($index_js_path), 'InnerBlocks') !== false;

        $handle = 'dbb-' . $folder;
        if (file_exists($index_js_path)) {
            wp_register_script(
                $handle,
                DBB_PLUGIN_URL . "blocks/$folder/index.js",
                ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'],
                filemtime($index_js_path),
                true
            );
        }

        if ($is_innerblock || $is_child) {
            // Solo bloques tipo InnerBlocks o hijos se registran con metadata (sin ACF)
            register_block_type_from_metadata($block_dir, [
                'editor_script' => $handle,
            ]);
        } else {
            // Bloques normales ACF (sin InnerBlocks ni hijos)
            $render_template_path = $block_dir . 'render.php';
            if (!file_exists($render_template_path)) {
                file_put_contents($render_template_path, "<?php\n// Render dinámico generado automáticamente\n");
            }

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
});


// Cargar scripts del editor
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'dbb-admin-ui',
        plugin_dir_url(__FILE__) . 'admin/admin-ui.js',
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
        filemtime(DBB_PLUGIN_DIR . 'admin/admin-ui.js')
    );
});

// Crear menú de administración
add_action('admin_menu', function () {
    add_menu_page(
        'Dynamic Blocks',
        'Dynamic Blocks',
        'manage_options',
        'dynamic-blocks',
        function () {
            echo '<div class="wrap"><div id="dbb-admin-root"></div></div>';
        },
        'dashicons-screenoptions'
    );
});

// En dynamic-blocks.php
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_dynamic-blocks') return;

    wp_enqueue_script(
        'dbb-admin-ui',
        plugin_dir_url(__FILE__) . 'admin/admin-ui.js',
        ['wp-element', 'wp-components', 'wp-api-fetch'],
        filemtime(__DIR__ . '/admin/admin-ui.js'),
        true
    );

    wp_enqueue_style('wp-components'); // Asegura estilos de Gutenberg

    wp_enqueue_style(
        'dbb-admin-ui',
        plugin_dir_url(__FILE__) . 'admin/admin-ui.css',
        [],
        filemtime(__DIR__ . '/admin/admin-ui.css')
    );    
});


add_action('rest_api_init', function () {

    register_rest_route('dynamic-blocks/v1', '/create', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function ($request) {
            $name = sanitize_title($request['blockName']);
            $title = sanitize_text_field($request['blockTitle']);
            $category = sanitize_text_field($request['category']);
            $icon = sanitize_text_field($request['icon']);
            $template = sanitize_text_field($request['template'] ?? 'default');
            $isChildBlock = !empty($request['isChildBlock']);
            $dir = DBB_PLUGIN_DIR . "blocks/$name/";

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

            // Editor JS según el tipo de bloque
            if ($template === 'innerblocks') {
                $index_js = <<<JS
                (function(blocks, blockEditor, element) {
                    var el = element.createElement;
                    var useBlockProps = blockEditor.useBlockProps;
                    var InnerBlocks = blockEditor.InnerBlocks;

                    blocks.registerBlockType('dbb/{$name}', {
                        title: '{$title}',
                        icon: '{$icon}',
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
                        icon: '{$icon}',
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

            // Guardar archivos
            file_put_contents($dir . 'block.json', json_encode($block_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            file_put_contents($dir . 'index.js', $index_js);

            return ['success' => true, 'name' => $name];
        }
    ]);
    
    register_rest_route('dynamic-blocks/v1', '/list', [
        'methods' => 'GET',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function () {
            $blocks = [];
            $dir = DBB_PLUGIN_DIR . 'blocks/';
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
    ]);


    register_rest_route('dynamic-blocks/v1', '/delete/(?P<name>[a-z0-9\-]+)', [
        'methods' => 'DELETE',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function ($request) {
            $name = sanitize_title($request['name']);
            $path = DBB_PLUGIN_DIR . "blocks/$name";

            if (!is_dir($path)) {
                return new WP_Error('not_found', 'Bloque no encontrado.', ['status' => 404]);
            }

            // Eliminar archivos y carpeta
            array_map('unlink', glob("$path/*.*"));
            rmdir($path);

            return ['deleted' => true, 'name' => $name];
        }
    ]);

    register_rest_route('dynamic-blocks/v1', '/get/(?P<name>[a-z0-9\-]+)', [
        'methods' => 'GET',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function ($request) {
            $name = sanitize_title($request['name']);
            $dir = DBB_PLUGIN_DIR . "blocks/$name/";

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
    ]);

    register_rest_route('dynamic-blocks/v1', '/update/(?P<name>[a-z0-9\-]+)', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function ($request) {
            $name = sanitize_title($request['name']);
            $dir = DBB_PLUGIN_DIR . "blocks/$name/";
            $block_json_path = $dir . 'block.json';
            $index_js_path = $dir . 'index.js';

            if (!file_exists($block_json_path)) {
                return new WP_Error('not_found', 'Bloque no encontrado.', ['status' => 404]);
            }

            // Valores a actualizar
            $title = sanitize_text_field($request['title']);
            $category = sanitize_text_field($request['category']);
            $icon = sanitize_text_field($request['icon']);
            $template = sanitize_text_field($request['template'] ?? 'default');
            $isChildBlock = !empty($request['isChildBlock']);

            // Cargar y actualizar block.json
            $block_json = json_decode(file_get_contents($block_json_path), true);
            $block_json['title'] = $title;
            $block_json['category'] = $category;
            $block_json['icon'] = $icon;
            $block_json['dbbIsChild'] = $isChildBlock;

            file_put_contents($block_json_path, json_encode($block_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Opcionalmente regenerar index.js si lo deseas (aquí se mantiene el mismo contenido)
            if (!empty($request['indexJs'])) {
                file_put_contents($index_js_path, $request['indexJs']);
            }

            return ['success' => true];
        }
    ]);
    
    register_rest_route('dynamic-blocks/v1', '/page-blocks/(?P<id>[a-zA-Z0-9-_]+)', [
        'methods'  => 'GET',
        'callback' => 'dbb_get_page_blocks',
    ]);

});

function dbb_generate_indexjs_from_acf($short_name, $title, $category, $icon, $acf_fields) {
    $attributes = [];
    $edit_controls = [];
    $save_controls = [];

    foreach ($acf_fields as $field) {
        $name = $field['name'];
        $label = $field['label'];
        $type = $field['type'];
        $attr_line = "$name: { type: 'string' }";
        $edit = '';
        $save = '';

        switch ($type) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
            case 'range':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: '$type',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'textarea':
                $edit = "el('div', {}, el('label', {}, '$label'), el('textarea', {
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'true_false':
                $attr_line = "$name: { type: 'boolean', default: false }";
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'checkbox',
                    checked: props.attributes['$name'],
                    onChange: (e) => props.setAttributes({ $name: e.target.checked })
                }))";
                $save = "el('p', {}, props.attributes['$name'] ? 'Sí' : 'No')";
                break;

            case 'select':
            case 'radio':
                $options = isset($field['choices']) ? $field['choices'] : [];
                $optionsJs = json_encode($options, JSON_UNESCAPED_SLASHES);
                $edit = <<<JS
                    el('div', {}, 
                        el('label', {}, '$label'),
                        el('select', {
                            value: props.attributes['$name'] || '',
                            onChange: (e) => props.setAttributes({ $name: e.target.value })
                        },
                            Object.entries($optionsJs).map(([val, text]) =>
                                el('option', { value: val }, text)
                            )
                        )
                    )
                    JS;
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'image':
            case 'file':
                $attr_line = "$name: { type: 'string' }";
                $edit = <<<JS
                    el(wp.blockEditor.MediaUpload, {
                        onSelect: (media) => props.setAttributes({ $name: media.url }),
                        allowedTypes: ['image'],
                        render: ({ open }) => el('div', {},
                            el('p', {}, '$label'),
                            props.attributes['$name'] 
                                ? el('img', { src: props.attributes['$name'], style: { maxWidth: '100%', height: 'auto' } }) 
                                : null,
                            el('button', { onClick: open }, 'Seleccionar imagen')
                        )
                    })
                    JS;
                $save = "el('img', { src: props.attributes['$name'] || '', alt: '$label' })";
                break;

            case 'gallery':
                $attr_line = "$name: { type: 'array', default: [] }";
                $edit = <<<JS
                    el(wp.blockEditor.MediaUpload, {
                        onSelect: (media) => props.setAttributes({ $name: media.map(m => m.url) }),
                        allowedTypes: ['image'],
                        multiple: true,
                        gallery: true,
                        render: ({ open }) => el('div', {},
                            el('p', {}, '$label'),
                            props.attributes['$name']?.map((url, i) => el('img', { key: i, src: url, style: { width: '80px', marginRight: '5px' } })),
                            el('div', { style: { marginTop: '10px' } },
                            el('button', { onClick: open }, 'Seleccionar imágenes')
                            )
                        )
                    })
                    JS;
                $save = "el('div', {}, props.attributes['$name']?.map((url, i) => el('img', { key: i, src: url, style: { width: '100px', marginRight: '5px' } })))";
                break;

            case 'color_picker':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'color',
                    value: props.attributes['$name'] || '#000000',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('div', { style: { backgroundColor: props.attributes['$name'], width: '50px', height: '20px' } })";
                break;

            case 'date_picker':
            case 'time_picker':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'text',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'wysiwyg':
                $edit = <<<JS
el(wp.blockEditor.RichText, {
    tagName: 'div',
    value: props.attributes['$name'] || '',
    onChange: (content) => props.setAttributes({ $name: content }),
    placeholder: '$label'
})
JS;
                $save = "el(wp.blockEditor.RichText.Content, {
    tagName: 'div',
    value: props.attributes['$name'] || ''
})";
                break;

            case 'repeater':
                // Soporte limitado: solo mostramos contador
                $attr_line = "$name: { type: 'string' }";
                $edit = "el('p', {}, '$label (campo repeater no editable desde bloque)')";
                $save = "el('p', {}, '$label (repeater)')";
                break;

            case 'post_object':
            case 'user':
            case 'taxonomy':
            case 'relationship':
                $edit = "el('div', {}, el('label', {}, '$label (ID)'), el('input', {
                    type: 'text',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, 'ID relacionado: ' + props.attributes['$name'])";
                break;

            default:
                $edit = "el('div', {}, el('label', {}, '$label ($type)'), el('input', {
                    type: 'text',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
        }

        $attributes[$name] = $attr_line;
        $edit_controls[] = $edit;
        $save_controls[] = $save;
    }

    $attributes_js = implode(",\n      ", $attributes);
    $edit_js = implode(",\n      ", $edit_controls);
    $save_js = implode(",\n      ", $save_controls);

    return <<<JS
    (function(blocks, blockEditor, element) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType('dbb/{$short_name}', {
        title: '{$title}',
        icon: '{$icon}',
        category: '{$category}',
        attributes: {
        $attributes_js
        },
        edit: function(props) {
        const blockProps = useBlockProps();
        return el('div', blockProps, 
            $edit_js
        );
        },
        save: function(props) {
        const blockProps = useBlockProps.save();
        return el('div', blockProps, 
            $save_js
        );
        }
    });
    })(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.element
    );
    JS;
}

add_action('acf/init', function () {
    if (!function_exists('acf_get_field_groups')) return;

    $groups = acf_get_field_groups();

    foreach ($groups as $group) {
        foreach ($group['location'] as $ruleset) {
            foreach ($ruleset as $rule) {
                if ($rule['param'] === 'block') {
                    $block_name = str_replace('acf/', '', $rule['value']);
                    $block_folder = str_replace('dbb-', '', $block_name);
                    $dir = DBB_PLUGIN_DIR . "blocks/$block_folder/";

                    if (!is_dir($dir)) continue;

                    $fields = acf_get_fields($group['key']);
                    if (!$fields) continue;

                    $content = "<?php\n";
                    $content .= "// Render generado dinámicamente por ACF\n\n";
                    $content .= "if (!function_exists('dbb_render_field')) {\n";
                    $content .= "function dbb_render_field(\$label, \$field) {\n";
                    $content .= "    if (is_array(\$field)) {\n";
                    $content .= "        if (isset(\$field['url'])) {\n";
                    $content .= "            echo '<p><strong>' . esc_html(\$label) . ':</strong> <a href=\"' . esc_url(\$field['url']) . '\" target=\"_blank\">' . esc_html(\$field['title'] ?? \$field['url']) . '</a></p>';\n";
                    $content .= "        } else {\n";
                    $content .= "            echo '<p><strong>' . esc_html(\$label) . ':</strong> <pre>' . esc_html(print_r(\$field, true)) . '</pre></p>';\n";
                    $content .= "        }\n";
                    $content .= "    } elseif (is_numeric(\$field)) {\n";
                    $content .= "        \$url = wp_get_attachment_url(\$field);\n";
                    $content .= "        if (\$url) echo '<p><strong>' . esc_html(\$label) . ':</strong> <img src=\"' . esc_url(\$url) . '\" style=\"max-width:200px;height:auto;\" /></p>';\n";
                    $content .= "        else echo '<p><strong>' . esc_html(\$label) . ':</strong> ID: ' . esc_html(\$field) . '</p>';\n";
                    $content .= "    } else {\n";
                    $content .= "        echo '<p><strong>' . esc_html(\$label) . ':</strong> ' . esc_html(\$field) . '</p>';\n";
                    $content .= "    }\n";
                    $content .= "}\n";
                    $content .= "}\n\n";


                    foreach ($fields as $field) {
                        $name = $field['name'];
                        $label = $field['label'];
                        $content .= "dbb_render_field('$label', get_field('$name'));\n";
                    }

                    file_put_contents($dir . 'render.php', $content);
                }
            }
        }
    }
});

function dbb_get_page_blocks($request) {
    $param = $request['id'];

    // Detectamos si es numérico (ID) o slug (texto)
    if (is_numeric($param)) {
        $post = get_post(intval($param));
    } else {
        $post = get_page_by_path(sanitize_title($param), OBJECT, 'page');
    }

    // Validación de post
    if (!$post || $post->post_type !== 'page') {
        return new WP_Error('not_found', 'Página no encontrada', ['status' => 404]);
    }

    // Obtener bloques y procesarlos
    $raw_blocks = parse_blocks($post->post_content);
    $processed_blocks = array_map('dbb_process_block', $raw_blocks);

    return [
        'post_id' => $post->ID,
        'title' => get_the_title($post->ID),
        'blocks' => $processed_blocks,
    ];
}



// 👇 Recursivo: procesa un bloque y sus innerBlocks
function dbb_process_block($block) {
    if (!isset($block['blockName'])) return null;

    $is_acf_block = strpos($block['blockName'], 'acf/dbb-') === 0;
    $attrs = $block['attrs'] ?? [];
    $data  = [];

    if ($is_acf_block && isset($attrs['data']) && is_array($attrs['data'])) {
        foreach ($attrs['data'] as $key => $value) {
            if (strpos($key, '_') === 0) continue;

            // Imagen por ID
            if (is_numeric($value) && wp_attachment_is_image($value)) {
                $data[$key] = [
                    'id'    => $value,
                    'url'   => wp_get_attachment_url($value),
                    'alt'   => get_post_meta($value, '_wp_attachment_image_alt', true),
                    'title' => get_the_title($value),
                ];
            } else {
                $data[$key] = $value;
            }
        }
    }

    $inner = [];
    if (!empty($block['innerBlocks'])) {
        $inner = array_map('dbb_process_block', $block['innerBlocks']);
        $inner = array_filter($inner); // Remueve nulos
    }

    return [
        'block' => $block['blockName'],
        'id' => $attrs['id'] ?? '',
        'attributes' => [
            'name'   => $attrs['name'] ?? '',
            'anchor' => $attrs['anchor'] ?? '',
            'mode'   => $attrs['mode'] ?? 'preview',
            'data'   => $data,
        ],
        'innerBlocks' => $inner,
    ];
}