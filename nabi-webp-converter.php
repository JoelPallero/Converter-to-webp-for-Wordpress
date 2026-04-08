<?php
/**
 * Plugin Name: Nabi WebP Converter
 * Description: Convierte automáticamente todas las imágenes subidas a formato WebP con 100% de calidad. Incluye opción para convertir imágenes existentes por lotes o selección individual.
 * Version: 1.0.1
 * Author: Joel Pallero
 * Author URI: https://joelpallero.com.ar
<<<<<<< HEAD
 * Plugin URI: https://joelpallero.com.ar/productos
=======
 * Plugin URI: https://joelpallero.com.ar/store/nabi-webp
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
 * Text Domain: Nabi-webp
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Plugin Icon: assets/icons/icon.svg
 */

defined('ABSPATH') || exit;

// Definiciones globales
define('NABI_WEBP_VERSION', '1.0.1');
define('NABI_WEBP_PATH', plugin_dir_path(__FILE__));
define('NABI_WEBP_URL', plugin_dir_url(__FILE__));

// Autocarga de clases
require_once NABI_WEBP_PATH . 'includes/class-nabi-webp-loader.php';
require_once NABI_WEBP_PATH . 'includes/class-nabi-webp-converter.php';
require_once NABI_WEBP_PATH . 'includes/class-nabi-webp-ajax.php';
require_once NABI_WEBP_PATH . 'admin/class-nabi-webp-admin.php';

// Hooks de inicialización
<<<<<<< HEAD
add_action('plugins_loaded', ['NABI_WebP_Loader', 'init']);
=======
add_action('plugins_loaded', function() {
    if (!class_exists('NABI_Master')) {
        require_once NABI_WEBP_PATH . 'includes/nabi-master/class-nabi-master.php';
    }
    NABI_Master::init();

    if (class_exists('NABI_WebP_Loader')) {
        NABI_WebP_Loader::init();
    }
});
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)

// Cargar textdomain
add_action('plugins_loaded', 'NABI_webp_load_textdomain');
function NABI_webp_load_textdomain() {
    load_plugin_textdomain('Nabi-webp', false, dirname(plugin_basename(__FILE__)) . '/languages');
}


