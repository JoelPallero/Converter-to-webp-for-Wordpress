<?php
<<<<<<< HEAD
=======
/**
 * Plugin Name: Nabi WebP Converter
 * Description: Convierte automáticamente imágenes JPG, PNG y GIF a formato WebP.
 * Version: 1.0.1
 * Author: Joel Pallero
 * Author URI: https://joelpallero.com.ar
 * Plugin URI: https://joelpallero.com.ar/store/nabi-webp
 * Text Domain: Nabi-webp
 */

>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
defined('ABSPATH') || exit;

/**
 * Clase para gestionar las configuraciones del plugin WebP Converter
 */
class NABI_WebP_Settings {

    /**
     * Obtiene todas las configuraciones
     */
    public static function get_settings() {
<<<<<<< HEAD
        return [
            'filter_year' => get_option('NABI_webp_filter_year', ''),
            'filter_month' => get_option('NABI_webp_filter_month', ''),
        ];
=======
        $defaults = [
            'compression_quality' => 80,
            'auto_convert' => true,
            'keep_original' => true,
            'filter_year' => '',
            'filter_month' => '',
        ];

        $saved = get_option('NABI_webp_settings', []);
        
        // Si no existe la opción nueva, intentar migrar de las antiguas si existían
        if (empty($saved)) {
            $saved = [
                'filter_year' => get_option('NABI_webp_filter_year', ''),
                'filter_month' => get_option('NABI_webp_filter_month', ''),
            ];
        }

        return array_merge($defaults, $saved);
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
    }

    /**
     * Guarda las configuraciones
     */
    public static function save_settings($settings) {
<<<<<<< HEAD
        update_option('NABI_webp_filter_year', isset($settings['filter_year']) ? sanitize_text_field($settings['filter_year']) : '');
        update_option('NABI_webp_filter_month', isset($settings['filter_month']) ? sanitize_text_field($settings['filter_month']) : '');
=======
        $old_settings = self::get_settings();
        $new_settings = array_merge($old_settings, $settings);
        
        update_option('NABI_webp_settings', $new_settings);
        
        // Mantener compatibilidad con los métodos antiguos si fuera necesario
        if (isset($settings['filter_year'])) {
            update_option('NABI_webp_filter_year', sanitize_text_field($settings['filter_year']));
        }
        if (isset($settings['filter_month'])) {
            update_option('NABI_webp_filter_month', sanitize_text_field($settings['filter_month']));
        }
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
        
        return true;
    }

    /**
     * Obtiene el año y mes configurados para filtrar
     */
    public static function get_filter_date() {
        $settings = self::get_settings();
        $year = $settings['filter_year'];
        $month = $settings['filter_month'];
        
        if (empty($year) || empty($month)) {
            return null;
        }
        
        return [
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT)
        ];
    }
}
<<<<<<< HEAD


=======
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
