<?php
defined('ABSPATH') || exit;

class DN325_WebP_Loader {

    public static function init() {
        if (is_admin()) {
            self::load_admin();
        }
        self::load_converter();
    }

    private static function load_admin() {
        if (class_exists('DN325_WebP_Admin')) {
            DN325_WebP_Admin::init();
        }
        if (class_exists('DN325_WebP_Ajax')) {
            DN325_WebP_Ajax::init();
        }
    }

    private static function load_converter() {
        if (class_exists('DN325_WebP_Converter')) {
            DN325_WebP_Converter::init();
        }
    }
}
