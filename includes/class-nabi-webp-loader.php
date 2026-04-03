<?php
defined('ABSPATH') || exit;

class NABI_WebP_Loader {

    public static function init() {
        if (is_admin()) {
            self::load_admin();
        }
        self::load_converter();
    }

    private static function load_admin() {
        if (class_exists('NABI_WebP_Admin')) {
            NABI_WebP_Admin::init();
        }
        if (class_exists('NABI_WebP_Ajax')) {
            NABI_WebP_Ajax::init();
        }
    }

    private static function load_converter() {
        if (class_exists('NABI_WebP_Converter')) {
            NABI_WebP_Converter::init();
        }
    }
}


