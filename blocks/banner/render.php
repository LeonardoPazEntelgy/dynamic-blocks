<?php
// Render generado dinámicamente por ACF

if (!function_exists('dbb_render_field')) {
function dbb_render_field($label, $field) {
    if (is_array($field)) {
        if (isset($field['url'])) {
            echo '<p><strong>' . esc_html($label) . ':</strong> <a href="' . esc_url($field['url']) . '" target="_blank">' . esc_html($field['title'] ?? $field['url']) . '</a></p>';
        } else {
            echo '<p><strong>' . esc_html($label) . ':</strong> <pre>' . esc_html(print_r($field, true)) . '</pre></p>';
        }
    } elseif (is_numeric($field)) {
        $url = wp_get_attachment_url($field);
        if ($url) echo '<p><strong>' . esc_html($label) . ':</strong> <img src="' . esc_url($url) . '" style="max-width:200px;height:auto;" /></p>';
        else echo '<p><strong>' . esc_html($label) . ':</strong> ID: ' . esc_html($field) . '</p>';
    } else {
        echo '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html($field) . '</p>';
    }
}
}

dbb_render_field('Background Color', get_field('banner_background_color'));
dbb_render_field('Imagen Desktop', get_field('banner_imagen_desktop'));
dbb_render_field('Imagen Tablet', get_field('banner_imagen_tablet'));
dbb_render_field('Imagen Mobile', get_field('banner_imagen_mobile'));
dbb_render_field('Titulo', get_field('banner_titulo'));
dbb_render_field('Texto', get_field('text_banner'));
dbb_render_field('Botón', get_field('banner_button'));
