<?php
defined('ABSPATH') || exit;

class DN325_WebP_Converter {

    private static $installed_time = null;

    public static function init() {
        // Obtener la fecha de instalación del plugin
        self::$installed_time = get_option('dn325_webp_installed_time', time());
        
        // Hook para convertir imágenes nuevas al subirlas
        add_filter('wp_handle_upload_prefilter', [__CLASS__, 'convert_uploaded_image'], 10, 1);
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'convert_attachment_metadata'], 10, 2);
        
        // Hook para API REST
        add_filter('rest_after_insert_attachment', [__CLASS__, 'convert_rest_attachment'], 10, 2);
    }

    /**
     * Convierte la imagen subida a WebP antes de guardarla
     */
    public static function convert_uploaded_image($file) {
        // Verificar si es una imagen
        if (!self::is_image($file['type'])) {
            return $file;
        }

        // Verificar si ya es WebP
        if ($file['type'] === 'image/webp') {
            return $file;
        }

        // Convertir a WebP
        $webp_path = self::convert_to_webp($file['tmp_name'], $file['type']);
        
        if ($webp_path && file_exists($webp_path)) {
            // Reemplazar el archivo temporal
            @unlink($file['tmp_name']);
            @rename($webp_path, $file['tmp_name']);
            $file['type'] = 'image/webp';
            $file['name'] = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file['name']);
        }

        return $file;
    }

    /**
     * Convierte todos los tamaños de la imagen después de generar los thumbnails
     */
    public static function convert_attachment_metadata($metadata, $attachment_id) {
        if (empty($metadata) || !isset($metadata['file'])) {
            return $metadata;
        }

        // Verificar si la imagen fue subida después de la instalación del plugin
        $upload_time = get_post_time('U', false, $attachment_id);
        if ($upload_time && $upload_time < self::$installed_time) {
            // No convertir imágenes antiguas automáticamente
            return $metadata;
        }

        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $file_path = $base_dir . '/' . $metadata['file'];
        $file_dir = dirname($file_path);
        
        // Verificar si el archivo ya es WebP (fue convertido en convert_uploaded_image)
        $current_mime = get_post_mime_type($attachment_id);
        if ($current_mime === 'image/webp') {
            // El archivo ya fue convertido, solo necesitamos actualizar los nombres en metadata si es necesario
            // WordPress ya debería haber generado los thumbnails correctamente
            return $metadata;
        }

        // Si llegamos aquí, el archivo no fue convertido en convert_uploaded_image
        // Obtener URL original antes de convertir
        $original_url = wp_get_attachment_url($attachment_id);
        $original_relative_path = str_replace($upload_dir['baseurl'], '', $original_url);
        $old_metadata_copy = $metadata;
        $webp_path = false;
        $file_converted = false;
        
        // Convertir el archivo original
        if (file_exists($file_path)) {
            $webp_path = self::convert_file_to_webp($file_path);
            if ($webp_path && $webp_path !== $file_path) {
                $file_converted = true;
                // Actualizar el nombre del archivo en metadata
                $metadata['file'] = str_replace($base_dir . '/', '', $webp_path);
                
                // Actualizar el archivo adjunto en la base de datos
                update_attached_file($attachment_id, $webp_path);
                
                // Actualizar tipo MIME
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    ['post_mime_type' => 'image/webp'],
                    ['ID' => $attachment_id],
                    ['%s'],
                    ['%d']
                );
            }
        }

        // Convertir todos los tamaños
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_data) {
                if (isset($size_data['file'])) {
                    $size_path = $file_dir . '/' . $size_data['file'];
                    if (file_exists($size_path)) {
                        $webp_size_path = self::convert_file_to_webp($size_path);
                        if ($webp_size_path && $webp_size_path !== $size_path) {
                            // Actualizar el nombre del archivo en metadata
                            $metadata['sizes'][$size]['file'] = basename($webp_size_path);
                        }
                    }
                }
            }
        }
        
        // No actualizar referencias aquí para imágenes nuevas (ya se convierten antes de guardarse)
        // Las referencias se actualizarán solo si es necesario más adelante

        return $metadata;
    }

    /**
     * Convierte imágenes subidas vía REST API
     */
    public static function convert_rest_attachment($attachment, $request) {
        $attachment_id = $attachment->ID;
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if ($metadata) {
            self::convert_attachment_metadata($metadata, $attachment_id);
        }
        
        return $attachment;
    }

    /**
     * Convierte un archivo específico a WebP
     * Retorna la ruta del archivo WebP si la conversión fue exitosa, o false en caso contrario
     */
    public static function convert_file_to_webp($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        // Verificación rápida de extensión antes de wp_check_filetype (más costoso)
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return false;
        }

        $file_info = wp_check_filetype($file_path);
        $mime_type = $file_info['type'];
        
        if (!self::is_image($mime_type) || $mime_type === 'image/webp') {
            return false;
        }

        $webp_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file_path);
        
        // Verificar si ya existe el WebP (evitar conversión duplicada)
        if (file_exists($webp_path)) {
            @unlink($file_path);
            return $webp_path;
        }
        
        if (self::convert_to_webp($file_path, $mime_type, $webp_path)) {
            // Eliminar el archivo original si la conversión fue exitosa
            if ($webp_path !== $file_path && file_exists($webp_path)) {
                @unlink($file_path);
            }
            return $webp_path;
        }
        
        return false;
    }

    /**
     * Convierte una imagen a WebP usando GD o Imagick
     */
    private static function convert_to_webp($source_path, $mime_type, $destination_path = null) {
        if (!file_exists($source_path)) {
            return false;
        }

        if ($destination_path === null) {
            $destination_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $source_path);
        }

        // Verificar si GD o Imagick están disponibles
        if (!function_exists('imagecreatefromjpeg') && !extension_loaded('imagick')) {
            return false;
        }

        $image = null;
        $quality = 100; // 100% de calidad como solicitado

        // Cargar la imagen según el tipo
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                if (function_exists('imagecreatefromjpeg')) {
                    $image = @imagecreatefromjpeg($source_path);
                } elseif (extension_loaded('imagick')) {
                    $imagick = new Imagick($source_path);
                    $imagick->setImageFormat('webp');
                    $imagick->setImageCompressionQuality($quality);
                    $imagick->writeImage($destination_path);
                    $imagick->clear();
                    $imagick->destroy();
                    return file_exists($destination_path) ? $destination_path : false;
                }
                break;
            
            case 'image/png':
                if (function_exists('imagecreatefrompng')) {
                    $image = @imagecreatefrompng($source_path);
                    // Preservar transparencia
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                } elseif (extension_loaded('imagick')) {
                    $imagick = new Imagick($source_path);
                    $imagick->setImageFormat('webp');
                    $imagick->setImageCompressionQuality($quality);
                    $imagick->writeImage($destination_path);
                    $imagick->clear();
                    $imagick->destroy();
                    return file_exists($destination_path) ? $destination_path : false;
                }
                break;
            
            case 'image/gif':
                if (extension_loaded('imagick')) {
                    $imagick = new Imagick($source_path);
                    $imagick->setImageFormat('webp');
                    $imagick->setImageCompressionQuality($quality);
                    $imagick->writeImage($destination_path);
                    $imagick->clear();
                    $imagick->destroy();
                    return file_exists($destination_path) ? $destination_path : false;
                }
                break;
        }

        if (!$image) {
            return false;
        }

        // Convertir a WebP usando GD
        if (function_exists('imagewebp')) {
            $result = imagewebp($image, $destination_path, $quality);
            imagedestroy($image);
            
            if ($result && file_exists($destination_path)) {
                // Optimizar el tamaño del archivo si es PNG (eliminar chunks innecesarios)
                if ($mime_type === 'image/png') {
                    // WebP ya está optimizado, no necesitamos hacer nada más
                }
                return $destination_path;
            }
        }

        return false;
    }

    /**
     * Verifica si un tipo MIME es una imagen
     */
    private static function is_image($mime_type) {
        return in_array($mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ]);
    }

    /**
     * Obtiene todas las imágenes que pueden ser convertidas
     */
    public static function get_convertible_images($limit = -1, $offset = 0) {
        require_once DN325_WEBP_PATH . 'includes/class-dn325-webp-settings.php';
        
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'inherit',
            'fields' => 'ids'
        ];

        // Aplicar filtro de año/mes si está configurado
        $filter_date = DN325_WebP_Settings::get_filter_date();
        if ($filter_date && !empty($filter_date['year']) && !empty($filter_date['month'])) {
            $args['date_query'] = [
                [
                    'year' => intval($filter_date['year']),
                    'month' => intval($filter_date['month']),
                ]
            ];
        }

        return get_posts($args);
    }

    /**
     * Convierte una imagen específica por ID
     * @param int $attachment_id ID del attachment
     * @param bool $skip_references Si es true, no actualiza referencias en BD (más rápido)
     */
    public static function convert_image_by_id($attachment_id, $skip_references = false) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return [
                'success' => false,
                'message' => __('Archivo no encontrado', 'dn325-webp')
            ];
        }

        $mime_type = get_post_mime_type($attachment_id);
        
        if (!self::is_image($mime_type) || $mime_type === 'image/webp') {
            return [
                'success' => false,
                'message' => __('La imagen ya es WebP o no es una imagen válida', 'dn325-webp')
            ];
        }

        $webp_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file_path);
        
        // Obtener metadatos antiguos antes de convertir
        $old_metadata = wp_get_attachment_metadata($attachment_id);
        
        // Obtener URLs originales antes de convertir (para actualizar referencias)
        $original_url = wp_get_attachment_url($attachment_id);
        $upload_dir = wp_upload_dir();
        $original_relative_path = str_replace($upload_dir['baseurl'], '', $original_url);
        
        if (self::convert_to_webp($file_path, $mime_type, $webp_path)) {
            // Actualizar el archivo adjunto
            update_attached_file($attachment_id, $webp_path);
            
            // Actualizar metadata solo si es necesario (evitar regeneración costosa)
            // Actualizar manualmente los metadatos básicos en lugar de regenerar todo
            $existing_metadata = wp_get_attachment_metadata($attachment_id);
            if ($existing_metadata) {
                // Actualizar solo el nombre del archivo en metadata
                $upload_dir = wp_upload_dir();
                $existing_metadata['file'] = str_replace($upload_dir['basedir'] . '/', '', $webp_path);
                wp_update_attachment_metadata($attachment_id, $existing_metadata);
                $metadata = $existing_metadata;
            } else {
                // Solo regenerar si no hay metadata existente
                $metadata = wp_generate_attachment_metadata($attachment_id, $webp_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }
            
            // Actualizar el tipo MIME en la base de datos
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                ['post_mime_type' => 'image/webp'],
                ['ID' => $attachment_id],
                ['%s'],
                ['%d']
            );
            
            // Eliminar el archivo original
            if ($webp_path !== $file_path && file_exists($file_path)) {
                @unlink($file_path);
            }
            
            // Actualizar referencias solo si no se omite (para mejorar rendimiento)
            if (!$skip_references) {
                $new_url = wp_get_attachment_url($attachment_id);
                $new_relative_path = str_replace($upload_dir['baseurl'], '', $new_url);
                
                // Actualizar todas las referencias en la base de datos
                self::update_image_references($attachment_id, $original_url, $new_url, $original_relative_path, $new_relative_path, $old_metadata, $metadata);
            }
            
            // Limpiar caché
            clean_post_cache($attachment_id);

            // Convertir todos los tamaños existentes (en segundo plano, sin esperar)
            if (isset($old_metadata['sizes']) && is_array($old_metadata['sizes']) && !$skip_references) {
                $file_dir = dirname($file_path);
                // Convertir thumbnails de forma asíncrona o en batch
                foreach ($old_metadata['sizes'] as $size => $size_data) {
                    if (isset($size_data['file'])) {
                        $size_path = $file_dir . '/' . $size_data['file'];
                        if (file_exists($size_path)) {
                            // Convertir sin actualizar referencias (más rápido)
                            self::convert_file_to_webp($size_path);
                        }
                    }
                }
            }

            return [
                'success' => true,
                'message' => __('Imagen convertida exitosamente', 'dn325-webp')
            ];
        }

        return [
            'success' => false,
            'message' => __('Error al convertir la imagen', 'dn325-webp')
        ];
    }

    /**
     * Actualiza todas las referencias a una imagen en la base de datos
     * Versión optimizada que hace menos queries
     */
    private static function update_image_references($attachment_id, $old_url, $new_url, $old_relative_path, $new_relative_path, $old_metadata, $new_metadata) {
        global $wpdb;
        
        // Solo actualizar las URLs principales (completa y relativa)
        // Las URLs de thumbnails se actualizarán automáticamente cuando WordPress las solicite
        $replacements = [
            [$old_url, $new_url],
            [$old_relative_path, $new_relative_path],
        ];
        
        // Agregar URL sin protocolo si es diferente
        $old_url_no_protocol = str_replace(['http://', 'https://'], '', $old_url);
        $new_url_no_protocol = str_replace(['http://', 'https://'], '', $new_url);
        if ($old_url_no_protocol !== $old_url) {
            $replacements[] = [$old_url_no_protocol, $new_url_no_protocol];
        }
        
        // Usar transacciones para mejorar rendimiento
        $wpdb->query('START TRANSACTION');
        
        try {
            // Actualizar posts (post_content y post_excerpt en una sola query optimizada)
            foreach ($replacements as $replacement) {
                list($old_pattern, $new_pattern) = $replacement;
                
                // Actualizar post_content y post_excerpt en una sola query
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->posts} 
                     SET post_content = REPLACE(post_content, %s, %s),
                         post_excerpt = REPLACE(post_excerpt, %s, %s)
                     WHERE post_content LIKE %s OR post_excerpt LIKE %s",
                    $old_pattern, $new_pattern,
                    $old_pattern, $new_pattern,
                    '%' . $wpdb->esc_like($old_pattern) . '%',
                    '%' . $wpdb->esc_like($old_pattern) . '%'
                ));
                
                // Actualizar postmeta, usermeta y options en queries separadas pero optimizadas
                // Solo actualizar si realmente contiene la URL
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) 
                     WHERE meta_value LIKE %s",
                    $old_pattern, $new_pattern,
                    '%' . $wpdb->esc_like($old_pattern) . '%'
                ));
                
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->usermeta} SET meta_value = REPLACE(meta_value, %s, %s) 
                     WHERE meta_value LIKE %s",
                    $old_pattern, $new_pattern,
                    '%' . $wpdb->esc_like($old_pattern) . '%'
                ));
                
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) 
                     WHERE option_value LIKE %s",
                    $old_pattern, $new_pattern,
                    '%' . $wpdb->esc_like($old_pattern) . '%'
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
        }
        
        // Limpiar caché de objetos (solo una vez al final)
        wp_cache_flush();
    }
}
