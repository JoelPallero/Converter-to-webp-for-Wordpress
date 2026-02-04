<?php
defined('ABSPATH') || exit;

class DN325_WebP_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
        // Guardar la fecha de instalación si no existe
        if (!get_option('dn325_webp_installed_time')) {
            update_option('dn325_webp_installed_time', time());
        }
    }

    /**
     * Agrega el menú de administración
     */
    public static function add_admin_menu() {
        // Intentar usar DN325_Menu del plugin de backup si existe
        $menu_class = null;
        
        // Buscar DN325_Menu en varios lugares posibles
        $possible_paths = [
            'dn325-backup-for-wordpress/includes/class-dn325-menu.php',
            'DN325 Backup for WordPress/includes/class-dn325-menu.php',
            'dn325-master/includes/class-dn325-menu.php',
            'DN325 Master/includes/class-dn325-menu.php',
        ];
        
        foreach ($possible_paths as $relative_path) {
            $full_path = WP_PLUGIN_DIR . '/' . $relative_path;
            if (file_exists($full_path)) {
                require_once $full_path;
                if (class_exists('DN325_Menu')) {
                    $menu_class = 'DN325_Menu';
                    break;
                }
            }
        }
        
        // Si no encontramos DN325_Menu, usar el local DN325_Menu
        if (!$menu_class) {
            require_once DN325_WEBP_PATH . 'includes/class-dn325-menu.php';
            if (class_exists('DN325_Menu')) {
                $menu_class = 'DN325_Menu';
            }
        }
        
        $menu_class::add_submenu(
            __('DN325 WebP Converter', 'dn325-webp'),
            __('WebP Converter', 'dn325-webp'),
            'dn325-webp',
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
        if (strpos($hook, 'dn325-webp') !== false || strpos($hook, 'dn325-plugins') !== false) {
            $is_webp_page = true;
        }
        
        // Verificar por parámetro GET
        if (isset($_GET['page']) && $_GET['page'] === 'dn325-webp') {
            $is_webp_page = true;
        }
        
        if (!$is_webp_page) {
            return;
        }

        wp_enqueue_script(
            'dn325-webp-admin',
            DN325_WEBP_URL . 'assets/js/admin.js',
            ['jquery'],
            DN325_WEBP_VERSION,
            true
        );

        wp_enqueue_style(
            'dn325-webp-admin',
            DN325_WEBP_URL . 'assets/css/admin.css',
            [],
            DN325_WEBP_VERSION
        );

        wp_localize_script('dn325-webp-admin', 'dn325WebP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dn325_webp_nonce'),
            'strings' => [
                'converting' => __('Convirtiendo imágenes...', 'dn325-webp'),
                'success' => __('Conversión completada exitosamente', 'dn325-webp'),
                'error' => __('Ocurrió un error durante la conversión', 'dn325-webp'),
                'no_images' => __('No se encontraron imágenes para convertir', 'dn325-webp'),
                'confirm_convert' => __('¿Estás seguro de que deseas convertir todas las imágenes existentes? Este proceso puede tardar varios minutos.', 'dn325-webp')
            ]
        ]);
    }

    /**
     * Renderiza la página de administración
     */
    public static function render_admin_page() {
        $total_images = DN325_WebP_Converter::get_convertible_images(-1, 0);
        $total_count = count($total_images);
        $installed_time = get_option('dn325_webp_installed_time', time());
        $installed_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $installed_time);
        ?>
        <div class="wrap dn325-webp-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="dn325-webp-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#converter" class="nav-tab nav-tab-active"><?php _e('Convertir', 'dn325-webp'); ?></a>
                    <a href="#settings" class="nav-tab"><?php _e('Configuración', 'dn325-webp'); ?></a>
                </nav>

                <div class="dn325-webp-tab-content">
                    <div id="converter-tab" class="tab-pane active">
                        <div class="dn325-webp-info">
                <div class="dn325-webp-card">
                    <h2><?php _e('Conversión Automática', 'dn325-webp'); ?></h2>
                    <p><?php _e('Todas las imágenes nuevas que subas (ya sea manualmente o por API) se convertirán automáticamente a formato WebP con 100% de calidad.', 'dn325-webp'); ?></p>
                    <p class="dn325-webp-installed">
                        <strong><?php _e('Plugin instalado el:', 'dn325-webp'); ?></strong> 
                        <?php echo esc_html($installed_date); ?>
                    </p>
                    <p class="dn325-webp-note">
                        <em><?php _e('Nota: Las imágenes subidas antes de esta fecha no se convertirán automáticamente para evitar conflictos con imágenes que puedan estar en uso.', 'dn325-webp'); ?></em>
                    </p>
                </div>

                <div class="dn325-webp-card">
                    <h2><?php _e('Conversión Manual', 'dn325-webp'); ?></h2>
                    <p><?php _e('Puedes convertir manualmente todas las imágenes existentes que fueron subidas antes de la instalación del plugin.', 'dn325-webp'); ?></p>
                    
                    <div class="dn325-webp-stats">
                        <p>
                            <strong><?php _e('Imágenes disponibles para conversión:', 'dn325-webp'); ?></strong>
                            <span id="dn325-webp-total-count"><?php echo esc_html($total_count); ?></span>
                        </p>
                    </div>

                    <div class="dn325-webp-actions">
                        <button type="button" id="dn325-webp-convert-btn" class="button button-primary button-large">
                            <?php _e('Convertir Todas las Imágenes', 'dn325-webp'); ?>
                        </button>
                        <button type="button" id="dn325-webp-scan-btn" class="button button-secondary button-large">
                            <?php _e('Actualizar Conteo', 'dn325-webp'); ?>
                        </button>
                    </div>

                    <div id="dn325-webp-progress" class="dn325-webp-progress" style="display: none;">
                        <div class="dn325-webp-progress-bar">
                            <div class="dn325-webp-progress-fill"></div>
                        </div>
                        <div id="dn325-webp-progress-details" class="dn325-webp-progress-details"></div>
                        <p class="dn325-webp-progress-text"></p>
                        <div class="dn325-webp-progress-stats">
                            <strong><?php _e('Progreso:', 'dn325-webp'); ?></strong>
                            <span id="dn325-webp-converted">0</span> / 
                            <span id="dn325-webp-total">0</span> 
                            <?php _e('imágenes convertidas', 'dn325-webp'); ?>
                        </div>
                    </div>

                    <div id="dn325-webp-result" class="dn325-webp-result"></div>
                </div>
            </div>
                    </div>

                    <div id="settings-tab" class="tab-pane">
                        <?php
                        require_once DN325_WEBP_PATH . 'includes/class-dn325-webp-settings.php';
                        $settings = DN325_WebP_Settings::get_settings();
                        
                        // Obtener años y meses disponibles
                        $current_year = date('Y');
                        $years = range($current_year, 2010); // Desde 2010 hasta el año actual
                        $months = range(1, 12);
                        ?>
                        <div class="dn325-webp-card">
                            <h2><?php _e('Configuración de Conversión', 'dn325-webp'); ?></h2>
                            <p><?php _e('Selecciona el año y mes para filtrar las imágenes a convertir. WordPress organiza las imágenes por año y mes.', 'dn325-webp'); ?></p>
                            
                            <form id="dn325-webp-settings-form">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="filter_year"><?php _e('Año', 'dn325-webp'); ?></label>
                                            </th>
                                            <td>
                                                <select name="filter_year" id="filter_year">
                                                    <option value=""><?php _e('Todos los años', 'dn325-webp'); ?></option>
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
                                                <label for="filter_month"><?php _e('Mes', 'dn325-webp'); ?></label>
                                            </th>
                                            <td>
                                                <select name="filter_month" id="filter_month">
                                                    <option value=""><?php _e('Todos los meses', 'dn325-webp'); ?></option>
                                                    <?php 
                                                    $month_names = [
                                                        1 => __('Enero', 'dn325-webp'),
                                                        2 => __('Febrero', 'dn325-webp'),
                                                        3 => __('Marzo', 'dn325-webp'),
                                                        4 => __('Abril', 'dn325-webp'),
                                                        5 => __('Mayo', 'dn325-webp'),
                                                        6 => __('Junio', 'dn325-webp'),
                                                        7 => __('Julio', 'dn325-webp'),
                                                        8 => __('Agosto', 'dn325-webp'),
                                                        9 => __('Septiembre', 'dn325-webp'),
                                                        10 => __('Octubre', 'dn325-webp'),
                                                        11 => __('Noviembre', 'dn325-webp'),
                                                        12 => __('Diciembre', 'dn325-webp'),
                                                    ];
                                                    foreach ($months as $month): 
                                                    ?>
                                                        <option value="<?php echo esc_attr($month); ?>" <?php selected($settings['filter_month'], $month); ?>>
                                                            <?php echo esc_html($month_names[$month]); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="description">
                                                    <?php _e('Nota: Si seleccionas un año y mes, solo se convertirán las imágenes de ese período específico.', 'dn325-webp'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php _e('Guardar Configuración', 'dn325-webp'); ?>
                                    </button>
                                </p>
                            </form>
                            
                            <div id="dn325-webp-settings-result" class="dn325-webp-result" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
