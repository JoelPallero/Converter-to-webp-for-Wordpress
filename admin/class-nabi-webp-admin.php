<?php
defined('ABSPATH') || exit;

class NABI_WebP_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
        // Guardar la fecha de instalación si no existe
        if (!get_option('NABI_webp_installed_time')) {
            update_option('NABI_webp_installed_time', time());
        }
    }

    /**
     * Agrega el menú de administración
     */
    public static function add_admin_menu() {
        // Si no existe NABI_Master, usar el local
        if (!class_exists('NABI_Master')) {
            require_once NABI_WEBP_PATH . 'includes/nabi-master/class-nabi-master.php';
        }
        
        NABI_Master::add_submenu(
<<<<<<< HEAD
            __('Nabi WebP Converter', 'Nabi-webp'),
            __('WebP Converter', 'Nabi-webp'),
            'Nabi-webp',
=======
            __('Nabi WebP Converter', 'nabi-webp'),
            __('WebP Converter', 'nabi-webp'),
            'nabi-webp',
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Carga los assets del admin
     */
    public static function enqueue_assets($hook) {
        // Verificar si estamos en la página del plugin
        $is_webp_page = false;
        
        // Verificar por hook
<<<<<<< HEAD
        if (strpos($hook, 'Nabi-webp') !== false || strpos($hook, 'Nabi-plugins') !== false) {
=======
        if (strpos($hook, 'nabi-webp') !== false) {
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
            $is_webp_page = true;
        }
        
        // Verificar por parámetro GET
<<<<<<< HEAD
        if (isset($_GET['page']) && $_GET['page'] === 'Nabi-webp') {
=======
        if (isset($_GET['page']) && $_GET['page'] === 'nabi-webp') {
>>>>>>> 589b2fb (Standardization of Nabi ecosystem and slug refactoring v1.0.1)
            $is_webp_page = true;
        }
        
        if (!$is_webp_page) {
            return;
        }

        wp_enqueue_script(
            'Nabi-webp-admin',
            NABI_WEBP_URL . 'assets/js/admin.js',
            ['jquery'],
            NABI_WEBP_VERSION,
            true
        );

        wp_enqueue_style(
            'Nabi-webp-admin',
            NABI_WEBP_URL . 'assets/css/admin.css',
            [],
            NABI_WEBP_VERSION
        );

        wp_localize_script('Nabi-webp-admin', 'NabiWebP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('NABI_webp_nonce'),
            'strings' => [
                'converting' => __('Convirtiendo imágenes...', 'Nabi-webp'),
                'success' => __('Conversión completada exitosamente', 'Nabi-webp'),
                'error' => __('Ocurrió un error durante la conversión', 'Nabi-webp'),
                'no_images' => __('No se encontraron imágenes para convertir', 'Nabi-webp'),
                'confirm_convert' => __('¿Estás seguro de que deseas convertir todas las imágenes existentes? Este proceso puede tardar varios minutos.', 'Nabi-webp')
            ]
        ]);
    }

    /**
     * Renderiza la página de administración
     */
    public static function render_admin_page() {
        $total_images = NABI_WebP_Converter::get_convertible_images(-1, 0);
        $total_count = count($total_images);
        $installed_time = get_option('NABI_webp_installed_time', time());
        $installed_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $installed_time);
        ?>
        <div class="wrap Nabi-webp-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="Nabi-webp-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#converter" class="nav-tab nav-tab-active"><?php _e('Convertir', 'Nabi-webp'); ?></a>
                    <a href="#settings" class="nav-tab"><?php _e('Configuración', 'Nabi-webp'); ?></a>
                </nav>

                <div class="Nabi-webp-tab-content">
                    <div id="converter-tab" class="tab-pane active">
                        <div class="Nabi-webp-info">
                <div class="Nabi-webp-card">
                    <h2><?php _e('Conversión Automática', 'Nabi-webp'); ?></h2>
                    <p><?php _e('Todas las imágenes nuevas que subas (ya sea manualmente o por API) se convertirán automáticamente a formato WebP con 100% de calidad.', 'Nabi-webp'); ?></p>
                    <p class="Nabi-webp-installed">
                        <strong><?php _e('Plugin instalado el:', 'Nabi-webp'); ?></strong> 
                        <?php echo esc_html($installed_date); ?>
                    </p>
                    <p class="Nabi-webp-note">
                        <em><?php _e('Nota: Las imágenes subidas antes de esta fecha no se convertirán automáticamente para evitar conflictos con imágenes que puedan estar en uso.', 'Nabi-webp'); ?></em>
                    </p>
                </div>

                <div class="Nabi-webp-card">
                    <h2><?php _e('Conversión Manual', 'Nabi-webp'); ?></h2>
                    <p><?php _e('Puedes convertir manualmente imágenes existentes que fueron subidas antes de la instalación del plugin.', 'Nabi-webp'); ?></p>
                    
                    <div class="Nabi-webp-stats">
                        <p>
                            <strong><?php _e('Imágenes disponibles para conversión:', 'Nabi-webp'); ?></strong>
                            <span id="Nabi-webp-total-count"><?php echo esc_html($total_count); ?></span>
                        </p>
                    </div>

                    <div class="Nabi-webp-selection-header">
                        <label>
                            <input type="checkbox" id="Nabi-webp-select-all">
                            <strong><?php _e('Seleccionar Todas', 'Nabi-webp'); ?></strong>
                        </label>
                        <span id="Nabi-webp-selected-count">0 <?php _e('seleccionadas', 'Nabi-webp'); ?></span>
                    </div>

                    <div id="Nabi-webp-images-list" class="Nabi-webp-images-list">
                        <!-- La lista se cargará vía AJAX -->
                        <div class="Nabi-webp-loading-images">
                            <span class="spinner is-active"></span> <?php _e('Cargando imágenes...', 'Nabi-webp'); ?>
                        </div>
                    </div>

                    <div class="Nabi-webp-actions">
                        <button type="button" id="Nabi-webp-convert-btn" class="button button-primary button-large">
                            <?php _e('Convertir Todas las Imágenes', 'Nabi-webp'); ?>
                        </button>
                        <button type="button" id="Nabi-webp-convert-selected-btn" class="button button-primary button-large" disabled style="display: none;">
                            <?php _e('Convertir Seleccionadas', 'Nabi-webp'); ?>
                        </button>
                        <button type="button" id="Nabi-webp-scan-btn" class="button button-secondary button-large">
                            <?php _e('Actualizar Lista', 'Nabi-webp'); ?>
                        </button>
                    </div>

                    <div id="Nabi-webp-progress" class="Nabi-webp-progress" style="display: none;">
                        <div class="Nabi-webp-progress-bar">
                            <div class="Nabi-webp-progress-fill"></div>
                        </div>
                        <div id="Nabi-webp-progress-details" class="Nabi-webp-progress-details"></div>
                        <p class="Nabi-webp-progress-text"></p>
                        <div class="Nabi-webp-progress-stats">
                            <strong><?php _e('Progreso:', 'Nabi-webp'); ?></strong>
                            <span id="Nabi-webp-converted">0</span> / 
                            <span id="Nabi-webp-total">0</span> 
                            <?php _e('imágenes procesadas', 'Nabi-webp'); ?>
                        </div>
                    </div>

                    <div id="Nabi-webp-result" class="Nabi-webp-result"></div>
                </div>
            </div>
                    </div>

                    <div id="settings-tab" class="tab-pane">
                        <?php
                        require_once NABI_WEBP_PATH . 'includes/class-nabi-webp-settings.php';
                        $settings = NABI_WebP_Settings::get_settings();
                        
                        // Obtener años y meses disponibles
                        $current_year = date('Y');
                        $years = range($current_year, 2010); // Desde 2010 hasta el año actual
                        $months = range(1, 12);
                        ?>
                        <div class="Nabi-webp-card">
                            <h2><?php _e('Configuración de Conversión', 'Nabi-webp'); ?></h2>
                            <p><?php _e('Selecciona el año y mes para filtrar las imágenes a convertir. WordPress organiza las imágenes por año y mes.', 'Nabi-webp'); ?></p>
                            
                            <form id="Nabi-webp-settings-form">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="filter_year"><?php _e('Año', 'Nabi-webp'); ?></label>
                                            </th>
                                            <td>
                                                <select name="filter_year" id="filter_year">
                                                    <option value=""><?php _e('Todos los años', 'Nabi-webp'); ?></option>
                                                    <?php foreach ($years as $year): ?>
                                                        <option value="<?php echo esc_attr($year); ?>" <?php selected($settings['filter_year'], $year); ?>>
                                                            <?php echo esc_html($year); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="filter_month"><?php _e('Mes', 'Nabi-webp'); ?></label>
                                            </th>
                                            <td>
                                                <select name="filter_month" id="filter_month">
                                                    <option value=""><?php _e('Todos los meses', 'Nabi-webp'); ?></option>
                                                    <?php 
                                                    $month_names = [
                                                        1 => __('Enero', 'Nabi-webp'),
                                                        2 => __('Febrero', 'Nabi-webp'),
                                                        3 => __('Marzo', 'Nabi-webp'),
                                                        4 => __('Abril', 'Nabi-webp'),
                                                        5 => __('Mayo', 'Nabi-webp'),
                                                        6 => __('Junio', 'Nabi-webp'),
                                                        7 => __('Julio', 'Nabi-webp'),
                                                        8 => __('Agosto', 'Nabi-webp'),
                                                        9 => __('Septiembre', 'Nabi-webp'),
                                                        10 => __('Octubre', 'Nabi-webp'),
                                                        11 => __('Noviembre', 'Nabi-webp'),
                                                        12 => __('Diciembre', 'Nabi-webp'),
                                                    ];
                                                    foreach ($months as $month): 
                                                    ?>
                                                        <option value="<?php echo esc_attr($month); ?>" <?php selected($settings['filter_month'], $month); ?>>
                                                            <?php echo esc_html($month_names[$month]); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="description">
                                                    <?php _e('Nota: Si seleccionas un año y mes, solo se convertirán las imágenes de ese período específico.', 'Nabi-webp'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php _e('Guardar Configuración', 'Nabi-webp'); ?>
                                    </button>
                                </p>
                            </form>
                            
                            <div id="Nabi-webp-settings-result" class="Nabi-webp-result" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}


