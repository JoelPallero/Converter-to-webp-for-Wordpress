<?php
defined('ABSPATH') || exit;

class DN325_WebP_Ajax {

    public static function init() {
        add_action('wp_ajax_dn325_webp_convert_all', [__CLASS__, 'convert_all_images']);
        add_action('wp_ajax_dn325_webp_convert_single', [__CLASS__, 'convert_single_image']);
        add_action('wp_ajax_dn325_webp_get_count', [__CLASS__, 'get_image_count']);
        add_action('wp_ajax_dn325_webp_update_references', [__CLASS__, 'update_all_references']);
        add_action('wp_ajax_dn325_webp_save_settings', [__CLASS__, 'handle_save_settings']);
    }

    /**
     * Obtiene el conteo de imágenes convertibles
     */
    public static function get_image_count() {
        check_ajax_referer('dn325_webp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'dn325-webp')]);
        }

        $images = DN325_WebP_Converter::get_convertible_images();
        $count = count($images);

        wp_send_json_success([
            'count' => $count
        ]);
    }

    /**
     * Convierte todas las imágenes
     */
    public static function convert_all_images() {
        // Aumentar tiempo de ejecución y memoria
        @set_time_limit(300); // 5 minutos
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');
        
        check_ajax_referer('dn325_webp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'dn325-webp')]);
        }

        // Reducir batch size para evitar timeouts
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 20;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $images = DN325_WebP_Converter::get_convertible_images($batch_size, $offset);
        
        if (empty($images)) {
            wp_send_json_success([
                'completed' => true,
                'message' => __('Todas las imágenes han sido convertidas', 'dn325-webp'),
                'converted' => 0,
                'total' => 0
            ]);
        }

        $converted = 0;
        $errors = 0;
        $results = [];
        
        // Deshabilitar hooks de WordPress para mejorar rendimiento
        remove_action('delete_attachment', 'wp_delete_attachment_files');
        
        // Procesar imágenes sin actualizar referencias individualmente (más rápido)
        $skip_references = isset($_POST['skip_references']) ? (bool)$_POST['skip_references'] : true;
        
        // Obtener tiempo de inicio para controlar duración
        $start_time = time();
        $max_execution_time = 240; // 4 minutos máximo por lote
        
        foreach ($images as $index => $attachment_id) {
            // Verificar tiempo transcurrido
            if ((time() - $start_time) > $max_execution_time) {
                // Si se está quedando sin tiempo, devolver lo procesado hasta ahora
                wp_send_json_success([
                    'completed' => false,
                    'converted' => $converted,
                    'errors' => $errors,
                    'processed' => $index,
                    'total' => count($images),
                    'offset' => $offset + $index,
                    'has_more' => true,
                    'timeout' => true,
                    'message' => sprintf(
                        __('Procesadas %d imágenes antes del timeout. Convertidas: %d, Errores: %d', 'dn325-webp'),
                        $index,
                        $converted,
                        $errors
                    )
                ]);
                return;
            }
            
            $result = DN325_WebP_Converter::convert_image_by_id($attachment_id, $skip_references);
            
            if ($result['success']) {
                $converted++;
            } else {
                $errors++;
            }
            
            // Solo agregar resultados si hay errores o si se solicita
            if (!$result['success'] || isset($_POST['include_results'])) {
                $results[] = [
                    'id' => $attachment_id,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }
        }
        
        // Re-habilitar hooks
        add_action('delete_attachment', 'wp_delete_attachment_files');

        $total_processed = $offset + count($images);
        
        // Solo obtener el total si es necesario (evitar consulta costosa en cada lote)
        // Usar una consulta más eficiente
        global $wpdb;
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/gif')
             AND post_status = 'inherit'"
        ));
        
        $has_more = $total_processed < $total_count;

        wp_send_json_success([
            'completed' => !$has_more,
            'converted' => $converted,
            'errors' => $errors,
            'processed' => count($images),
            'total' => $total_count,
            'offset' => $total_processed,
            'has_more' => $has_more,
            'results' => $results,
            'message' => sprintf(
                __('Procesadas %d imágenes. Convertidas: %d, Errores: %d', 'dn325-webp'),
                count($images),
                $converted,
                $errors
            )
        ]);
    }

    /**
     * Convierte una imagen individual
     */
    public static function convert_single_image() {
        check_ajax_referer('dn325_webp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'dn325-webp')]);
        }

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('ID de imagen no válido', 'dn325-webp')]);
        }

        $result = DN325_WebP_Converter::convert_image_by_id($attachment_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Actualiza todas las referencias de imágenes convertidas en batch
     */
    public static function update_all_references() {
        check_ajax_referer('dn325_webp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'dn325-webp')]);
        }

        // Obtener todas las imágenes WebP convertidas
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => 'image/webp',
            'posts_per_page' => -1,
            'post_status' => 'inherit',
            'fields' => 'ids'
        ];

        $webp_images = get_posts($args);
        $updated = 0;

        foreach ($webp_images as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            if (!$file_path || !file_exists($file_path)) {
                continue;
            }

            // Obtener el nombre base del archivo (sin extensión)
            $file_info = pathinfo($file_path);
            $base_name = $file_info['filename'];
            $upload_dir = wp_upload_dir();
            $file_dir = str_replace($upload_dir['basedir'], '', $file_info['dirname']);

            // Buscar posibles archivos originales (jpg, jpeg, png, gif)
            $extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $old_urls = [];
            $new_url = wp_get_attachment_url($attachment_id);
            $new_relative_path = str_replace($upload_dir['baseurl'], '', $new_url);

            foreach ($extensions as $ext) {
                $possible_old_path = $file_info['dirname'] . '/' . $base_name . '.' . $ext;
                if (file_exists($possible_old_path . '.webp')) {
                    // El WebP existe, buscar referencias al original
                    $old_relative = $file_dir . '/' . $base_name . '.' . $ext;
                    $old_full = $upload_dir['baseurl'] . $old_relative;
                    $old_urls[] = [$old_full, $new_url];
                    $old_urls[] = [$old_relative, $new_relative_path];
                }
            }

            // Actualizar referencias si hay URLs antiguas encontradas
            if (!empty($old_urls)) {
                global $wpdb;
                foreach ($old_urls as $url_pair) {
                    list($old_url, $new_url_replace) = $url_pair;
                    
                    // Actualizar posts
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->posts} 
                         SET post_content = REPLACE(post_content, %s, %s),
                             post_excerpt = REPLACE(post_excerpt, %s, %s)
                         WHERE post_content LIKE %s OR post_excerpt LIKE %s",
                        $old_url, $new_url_replace,
                        $old_url, $new_url_replace,
                        '%' . $wpdb->esc_like($old_url) . '%',
                        '%' . $wpdb->esc_like($old_url) . '%'
                    ));
                }
                $updated++;
            }
        }

        wp_send_json_success([
            'updated' => $updated,
            'total' => count($webp_images),
            'message' => sprintf(__('Actualizadas referencias para %d imágenes', 'dn325-webp'), $updated)
        ]);
    }
}
