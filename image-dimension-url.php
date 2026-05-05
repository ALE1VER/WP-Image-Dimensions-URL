<?php
/**
 * Plugin Name: Image Dimensions URL
 * Description: A WordPress plugin to check image dimensions of a webpage.
 * Version: 1.5
 * Author: javierenweb.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

// Agregar una página en el menú de administración de WordPress
function idc_add_admin_menu() {
    add_menu_page(
        'Image Dimensions Checker',
        'Image Dimensions',
        'manage_options',
        'idc_image_dimensions',
        'idc_display_image_dimensions',
        'dashicons-images-alt2'
    );
}
add_action('admin_menu', 'idc_add_admin_menu');

// Función para descargar CSV antes de cualquier salida
function idc_check_for_csv_download() {
    if (
        isset($_POST['download_csv'], $_POST['idc_csv_nonce_field']) &&
        wp_verify_nonce($_POST['idc_csv_nonce_field'], 'idc_csv_download_nonce')
    ) {
        idc_download_csv();
        exit();
    }
}
add_action('admin_init', 'idc_check_for_csv_download');

/**
 * Valida que la URL no apunte a IPs privadas o reservadas (anti-SSRF).
 */
function idc_is_safe_url($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;

    if (in_array(strtolower($host), ['localhost', '::1'], true)) return false;

    $ip = filter_var($host, FILTER_VALIDATE_IP);
    if ($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
    }
    return true;
}

/**
 * Obtiene las dimensiones reales de una imagen.
 * Usa getimagesize() primero y wp_remote_get() como fallback.
 */
function idc_get_real_dimensions($src) {
    $size = @getimagesize($src);
    if ($size && $size[0] && $size[1]) {
        return [(int)$size[0], (int)$size[1]];
    }

    $response = wp_remote_get($src, ['timeout' => 10]);
    if (is_wp_error($response)) return [0, 0];

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) return [0, 0];

    $tmp = tmpfile();
    if (!$tmp) return [0, 0];
    fwrite($tmp, $body);
    $meta = stream_get_meta_data($tmp);
    $size = @getimagesize($meta['uri']);
    fclose($tmp);

    if ($size && $size[0] && $size[1]) {
        return [(int)$size[0], (int)$size[1]];
    }
    return [0, 0];
}

/**
 * Resuelve URLs relativas a absolutas usando la URL base de la página.
 */
function idc_resolve_src($src, $base_url) {
    if (empty($src)) return '';
    if (preg_match('/^https?:\/\//i', $src)) return $src;
    if (strpos($src, '//') === 0) {
        $scheme = parse_url($base_url, PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $src;
    }
    if (strpos($src, '/') === 0) {
        $parts = parse_url($base_url);
        return $parts['scheme'] . '://' . $parts['host'] . $src;
    }
    return rtrim($base_url, '/') . '/' . $src;
}

// Función principal — muestra el checker
function idc_display_image_dimensions() {
    echo '<h1>Image Dimensions Checker</h1>';

    if (
        isset($_POST['url'], $_POST['idc_nonce_field']) &&
        wp_verify_nonce($_POST['idc_nonce_field'], 'idc_check_url_nonce')
    ) {
        $url = esc_url_raw(sanitize_text_field($_POST['url']));

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            echo '<p style="color:red;">Error: La URL proporcionada no es válida.</p>';
            idc_render_form();
            return;
        }

        if (!idc_is_safe_url($url)) {
            echo '<p style="color:red;">Error: La URL apunta a un recurso interno no permitido.</p>';
            idc_render_form();
            return;
        }

        $response = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($response)) {
            echo '<p style="color:red;">Error al obtener el contenido de la URL: ' . esc_html($response->get_error_message()) . '</p>';
            idc_render_form();
            return;
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            echo '<p style="color:red;">Error: La página no devolvió contenido.</p>';
            idc_render_form();
            return;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $images = $doc->getElementsByTagName('img');

        if ($images->length === 0) {
            echo '<p>No se encontraron imágenes en la URL proporcionada.</p>';
            idc_render_form();
            return;
        }

        $limit     = 150;
        $total     = $images->length;
        $processed = 0;

        echo '<h2>Imágenes en <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></h2>';

        if ($total > $limit) {
            echo '<p style="color:orange;">⚠️ Se encontraron ' . (int)$total . ' imágenes. Se muestran las primeras ' . (int)$limit . '.</p>';
        }

        echo '<table border="1" cellspacing="0" cellpadding="5" style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr style="background:#f1f1f1;">
                    <th style="width:60px;">Preview</th>
                    <th>URL de la imagen</th>
                    <th style="width:180px;">Dimensiones HTML</th>
                    <th style="width:180px;">Dimensiones reales</th>
                    <th style="width:90px;">Estado</th>
                  </tr>
                </thead>
                <tbody>';

        $csv_data = [];

        foreach ($images as $image) {
            if ($processed >= $limit) break;

            // Soporte lazy load
            $src = $image->getAttribute('src');
            if (empty($src) || strpos($src, 'data:') === 0) {
                foreach (['data-src', 'data-lazy-src', 'data-lazy', 'data-original'] as $attr) {
                    $lazy = $image->getAttribute($attr);
                    if (!empty($lazy) && strpos($lazy, 'data:') === false) {
                        $src = $lazy;
                        break;
                    }
                }
            }

            if (empty($src)) continue;

            $src = idc_resolve_src($src, $url);
            if (empty($src)) continue;

            $html_width  = (int)$image->getAttribute('width');
            $html_height = (int)$image->getAttribute('height');

            list($real_width, $real_height) = idc_get_real_dimensions($src);

            $has_html_dims = $html_width > 0 && $html_height > 0;
            $has_real_dims = $real_width > 0 && $real_height > 0;
            $mismatch      = $has_html_dims && $has_real_dims &&
                             ($html_width !== $real_width || $html_height !== $real_height);

            $row_style = $mismatch ? 'background:#fff0f0;' : '';

            if (!$has_real_dims) {
                $status = '<span style="color:gray;">—</span>';
            } elseif ($mismatch) {
                $status = '<span style="color:red;">⚠️ Mismatch</span>';
            } else {
                $status = '<span style="color:green;">✓ OK</span>';
            }

            $html_dims_label = $has_html_dims
                ? $html_width . ' x ' . $html_height . ' px'
                : '<span style="color:gray;">No declaradas</span>';

            $real_dims_label = $has_real_dims
                ? $real_width . ' x ' . $real_height . ' px'
                : '<span style="color:gray;">No disponible</span>';

            echo '<tr style="' . $row_style . '">
                    <td style="text-align:center;"><img src="' . esc_url($src) . '" class="image-thumbnail" /></td>
                    <td><a href="' . esc_url($src) . '" target="_blank">' . esc_html($src) . '</a></td>
                    <td style="text-align:center;">' . $html_dims_label . '</td>
                    <td style="text-align:center;">' . $real_dims_label . '</td>
                    <td style="text-align:center;">' . $status . '</td>
                  </tr>';

            $csv_status = $mismatch ? 'Mismatch' : (!$has_real_dims ? 'Sin datos' : 'OK');
            $csv_data[] = [
                $src,
                $has_html_dims ? $html_width . 'x' . $html_height : 'No declaradas',
                $has_real_dims ? $real_width . 'x' . $real_height : 'No disponible',
                $csv_status,
            ];

            $processed++;
        }

        echo '</tbody></table>';

        // Guardar en transient en lugar de campo oculto
        $transient_key = 'idc_csv_' . get_current_user_id();
        set_transient($transient_key, $csv_data, 10 * MINUTE_IN_SECONDS);

        echo '<br>
              <form method="post">
                <input type="hidden" name="idc_transient_key" value="' . esc_attr($transient_key) . '" />
                ' . wp_nonce_field('idc_csv_download_nonce', 'idc_csv_nonce_field', false, false) . '
                <input type="submit" name="download_csv" value="⬇️ Descargar reporte CSV" class="button button-primary" />
              </form>';

        echo '<p style="color:gray;font-size:12px;">Total imágenes encontradas: ' . (int)$total . ' | Procesadas: ' . (int)$processed . '</p>';
    }

    idc_render_form();
}

/**
 * Formulario de búsqueda de URL.
 */
function idc_render_form() {
    $current_url = isset($_POST['url']) ? esc_attr(sanitize_text_field($_POST['url'])) : '';
    echo '<br>
          <form method="post">
            <label for="url"><strong>Introduce la URL de la página:</strong></label><br><br>
            <input type="text" name="url" id="url" size="60" value="' . $current_url . '" placeholder="https://ejemplo.com/pagina" required>
            &nbsp;<input type="submit" value="Ver dimensiones" class="button button-secondary" />
            ' . wp_nonce_field('idc_check_url_nonce', 'idc_nonce_field', false, false) . '
          </form>';
}

// Función para generar y descargar el CSV
function idc_download_csv() {
    if (
        !isset($_POST['idc_csv_nonce_field']) ||
        !wp_verify_nonce($_POST['idc_csv_nonce_field'], 'idc_csv_download_nonce')
    ) return;

    $transient_key = isset($_POST['idc_transient_key']) ? sanitize_text_field($_POST['idc_transient_key']) : '';
    $csv_data      = $transient_key ? get_transient($transient_key) : false;

    if (!$csv_data) return;

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="image_dimensions_report.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

    fputcsv($output, ['URL Imagen', 'Dimensiones HTML', 'Dimensiones Reales', 'Estado']);
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Estilos para miniaturas
function idc_add_custom_styles() {
    echo '<style>
            .image-thumbnail {
                width: 50px;
                height: 50px;
                object-fit: contain;
            }
          </style>';
}
add_action('admin_head', 'idc_add_custom_styles');
