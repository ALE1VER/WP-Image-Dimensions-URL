<?php
/**
 * Plugin Name: WP Image Dimensions URL
 * Description: Plugin para obtener las dimensiones de las imágenes dentro de una página de WordPress.
 * Version: 1.4
 * Author: javierenweb.com
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
    if (isset($_POST['download_csv']) && isset($_POST['csv_data'])) {
        idc_download_csv();
        exit();
    }
}
add_action('admin_init', 'idc_check_for_csv_download');

// Función que obtiene y muestra las dimensiones de las imágenes de la página
function idc_display_image_dimensions() {
    echo '<h1>Image Dimensions Checker</h1>';

    // Verifica si se ha enviado una URL
    if (isset($_POST['url']) && isset($_POST['idc_nonce_field']) && wp_verify_nonce($_POST['idc_nonce_field'], 'idc_check_url_nonce')) {
        $url = sanitize_text_field($_POST['url']);

        // Validar URL
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            echo '<p style="color:red;">Error: La URL proporcionada no es válida.</p>';
            return;
        }

        // Usar wp_remote_get() para obtener contenido de la URL
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            echo '<p style="color:red;">Error al obtener el contenido de la URL.</p>';
            return;
        }

        $html = wp_remote_retrieve_body($response);

        // Crear un objeto DOMDocument para analizar el HTML
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        // Obtener todas las imágenes de la página
        $images = $doc->getElementsByTagName('img');

        echo '<h2>Dimensiones de las imágenes en ' . esc_url($url) . '</h2>';
        echo '<table border="1" cellspacing="0" cellpadding="5"><tr><th>Imagen</th><th>Enlace</th><th>Dimensiones</th></tr>';

        $csv_data = []; // Array para almacenar los datos CSV

        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            $width = $image->getAttribute('width');
            $height = $image->getAttribute('height');

            if (!$width || !$height) {
                list($width, $height) = @getimagesize($src);
            }

            // Crear miniatura de 50x50 px sin deformar
            $thumbnail_url = esc_url($src); // Deberías cambiar esto para generar la miniatura si no tienes una ya disponible

            echo '<tr>
                    <td><img src="' . $thumbnail_url . '" class="image-thumbnail" /></td>
                    <td><a href="' . esc_url($src) . '" target="_blank">' . esc_url($src) . '</a></td>
                    <td>' . esc_html($width) . ' x ' . esc_html($height) . ' px</td>
                  </tr>';

            $csv_data[] = [$src, $width . 'x' . $height];
        }

        echo '</table>';

        // Convertir datos CSV en una cadena para enviarlos en un campo oculto
        $csv_string = base64_encode(json_encode($csv_data));

        echo '<form method="post">
                <input type="hidden" name="csv_data" value="' . esc_attr($csv_string) . '" />
                <input type="submit" name="download_csv" value="Descargar reporte CSV" />
              </form>';
    }

    // Formulario para ingresar la URL
    echo '<form method="post">
            <label for="url">Introduce la URL de la página:</label><br>
            <input type="text" name="url" id="url" size="50" required>
            <input type="submit" value="Ver dimensiones" />
            ' . wp_nonce_field('idc_check_url_nonce', 'idc_nonce_field', false, false) . '
          </form>';
}

// Función para generar y descargar el CSV
function idc_download_csv() {
    if (!isset($_POST['csv_data'])) return;

    $csv_data = json_decode(base64_decode($_POST['csv_data']), true);
    if (!$csv_data) return;

    // Establecer cabeceras para la descarga del CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="image_dimensions_report.csv"');

    // Abrir la salida de PHP como archivo
    $output = fopen('php://output', 'w');

    // Escribir encabezados del CSV
    fputcsv($output, ['Imagen', 'Dimensiones']);

    // Escribir los datos
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }

    // Cerrar el archivo
    fclose($output);
    exit(); // Terminar ejecución del script
}

// Agregar estilo para las miniaturas
function idc_add_custom_styles() {
    echo '<style>
            .image-thumbnail {
                width: 50px; /* Ancho del contenedor */
                height: 50px; /* Alto del contenedor */
                object-fit: contain; /* Asegura que la imagen se ajuste proporcionalmente */
            }
          </style>';
}
add_action('admin_head', 'idc_add_custom_styles');
?>