<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package NABI_WebP_Converter
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
function NABI_webp_converter_delete_database_options() {
    global $wpdb;
    
    // Eliminar opciones que puedan estar relacionadas con el plugin
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'NABI_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_NABI_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_NABI_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_NABI_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_NABI_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_vira_webp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_vira_webp_%'");
    
    // Limpiar meta de usuarios si hay alguna
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'NABI_webp_%' OR meta_key LIKE 'vira_webp_%'");
    
    // Limpiar meta de posts si hay alguna
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'NABI_webp_%' OR meta_key LIKE 'vira_webp_%'");
}

// Ejecutar limpieza
NABI_webp_converter_delete_database_options();


