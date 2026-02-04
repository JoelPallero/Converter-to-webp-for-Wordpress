<?php
/**
 * Plugin Name: DN325 WebP Converter
 * Description: Convierte automáticamente todas las imágenes subidas a formato WebP con 100% de calidad. Incluye opción para convertir imágenes existentes.
 * Version: 1.0.0
 * Author: Joel Pallero
 * Author URI: https://joelpallero.com.ar
 * Plugin URI: https://joelpallero.com.ar/productos
 * Text Domain: dn325-webp
 * Requires at least: 6.0
 * Requires PHP: 7.6
 * Plugin Icon: assets/icons/icon.svg
 */

defined('ABSPATH') || exit;

// Definiciones globales
define('DN325_WEBP_VERSION', '1.0.0');
define('DN325_WEBP_PATH', plugin_dir_path(__FILE__));
define('DN325_WEBP_URL', plugin_dir_url(__FILE__));

// Autocarga de clases
require_once DN325_WEBP_PATH . 'includes/class-dn325-webp-loader.php';
require_once DN325_WEBP_PATH . 'includes/class-dn325-webp-converter.php';
require_once DN325_WEBP_PATH . 'includes/class-dn325-webp-ajax.php';
require_once DN325_WEBP_PATH . 'admin/class-dn325-webp-admin.php';

// Hooks de inicialización
add_action('plugins_loaded', ['DN325_WebP_Loader', 'init']);

// Cargar textdomain
add_action('plugins_loaded', 'dn325_webp_load_textdomain');
function dn325_webp_load_textdomain() {
    load_plugin_textdomain('dn325-webp', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
