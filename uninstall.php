<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package DN325_WebP_Converter
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar permisos
if (!current_user_can('activate_plugins')) {
    return;
}

/**
 * Elimina todas las opciones/configuraciones de la base de datos
 */
function dn325_webp_converter_delete_database_options() {
    global $wpdb;
    
    // Eliminar opciones que puedan estar relacionadas con el plugin
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dn325_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dn325_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dn325_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_dn325_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_dn325_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_vira_webp_%'");
    
    // Limpiar meta de usuarios si hay alguna
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dn325_webp_%' OR meta_key LIKE 'vira_webp_%'");
    
    // Limpiar meta de posts si hay alguna
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dn325_webp_%' OR meta_key LIKE 'vira_webp_%'");
}

// Ejecutar limpieza
dn325_webp_converter_delete_database_options();
