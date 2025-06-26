<?php
if (!function_exists('get_field')) {
    echo '<p><em>ACF no está activo.</em></p>';
    return;
}

$post_id = get_the_ID();
$fields = get_fields($post_id);

if (!$fields) {
    echo '<div class="wp-block-group is-style-default" style="padding: 2em; background: #f3f4f6; text-align: center;">';
    echo '<p><em>No hay campos ACF disponibles para este bloque.</em></p>';
    echo '</div>';
    return;
}

echo '<div class="wp-block-group is-style-default acf-block-render" style="padding:2em;">';

foreach ($fields as $key => $value) {
    // Si es imagen de WordPress por ID
    if (is_numeric($value) && $url = wp_get_attachment_url($value)) {
        echo '<div class="acf-image" style="margin-bottom: 1em;">';
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($key) . '" style="max-width:100%; height:auto;" />';
        echo '</div>';
        continue;
    }

    // Si es imagen como string de URL
    if (is_string($value) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $value)) {
        echo '<div class="acf-image" style="margin-bottom: 1em;">';
        echo '<img src="' . esc_url($value) . '" alt="' . esc_attr($key) . '" style="max-width:100%; height:auto;" />';
        echo '</div>';
        continue;
    }

    // Si es un enlace
    if (is_array($value) && isset($value['url']) && isset($value['title'])) {
        echo '<div class="acf-button" style="margin: 1em 0;">';
        echo '<a href="' . esc_url($value['url']) . '" class="wp-block-button__link wp-element-button" target="' . esc_attr($value['target'] ?? '_self') . '">';
        echo esc_html($value['title']);
        echo '</a>';
        echo '</div>';
        continue;
    }

    // Si es texto con indicios de título
    if (is_string($value) && preg_match('/title|titulo/i', $key)) {
        echo '<h2>' . esc_html($value) . '</h2>';
        continue;
    }

    // Si es texto con indicios de párrafo
    if (is_string($value) && preg_match('/text|descripcion|contenido/i', $key)) {
        echo '<p>' . esc_html($value) . '</p>';
        continue;
    }

    // Array genérico
    if (is_array($value)) {
        echo '<div class="acf-array" style="margin-bottom: 1em;">';
        echo '<strong>' . esc_html($key) . ':</strong><ul>';
        foreach ($value as $item) {
            echo '<li>' . esc_html(is_array($item) ? json_encode($item) : $item) . '</li>';
        }
        echo '</ul></div>';
        continue;
    }

    // Enlace simple
    if (wp_http_validate_url($value)) {
        echo '<p><strong>' . esc_html($key) . ':</strong> <a href="' . esc_url($value) . '">' . esc_html($value) . '</a></p>';
        continue;
    }

    // Valor por defecto
    echo '<p><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</p>';
}

echo '</div>';
