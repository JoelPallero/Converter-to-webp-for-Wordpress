<?php
defined('ABSPATH') || exit;

/**
 * Clase para gestionar las configuraciones del plugin WebP Converter
 */
class DN325_WebP_Settings {

    /**
     * Obtiene todas las configuraciones
     */
    public static function get_settings() {
        return [
            'filter_year' => get_option('dn325_webp_filter_year', ''),
            'filter_month' => get_option('dn325_webp_filter_month', ''),
        ];
    }

    /**
     * Guarda las configuraciones
     */
    public static function save_settings($settings) {
        update_option('dn325_webp_filter_year', isset($settings['filter_year']) ? sanitize_text_field($settings['filter_year']) : '');
        update_option('dn325_webp_filter_month', isset($settings['filter_month']) ? sanitize_text_field($settings['filter_month']) : '');
        
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
