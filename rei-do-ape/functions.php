<?php
if (!defined('ABSPATH')) exit;

define('REI_DO_APE_VERSION', '1.0.0');

function rei_do_ape_setup() {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    register_nav_menus(['primary' => 'Menu Principal']);
}
add_action('after_setup_theme', 'rei_do_ape_setup');

function rei_do_ape_scripts() {
    wp_enqueue_style('rei-do-ape-fonts', 'https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist-sans/style.css', [], REI_DO_APE_VERSION);
    wp_enqueue_style('rei-do-ape-style', get_stylesheet_uri(), ['rei-do-ape-fonts'], REI_DO_APE_VERSION);
    wp_enqueue_style('rei-do-ape-theme', get_template_directory_uri() . '/css/theme.css', ['rei-do-ape-style'], REI_DO_APE_VERSION);

    if (is_page('catalogo')) {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
        wp_enqueue_script('rei-do-ape-catalogo', get_template_directory_uri() . '/js/catalogo.js', ['leaflet'], REI_DO_APE_VERSION, true);
    }

    if (is_page('detalhe')) {
        wp_enqueue_script('rei-do-ape-detalhe', get_template_directory_uri() . '/js/detalhe.js', [], REI_DO_APE_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'rei_do_ape_scripts');

function rei_do_ape_register_apis() {
    register_rest_route('rei-do-ape/v1', '/orulo', [
        'methods' => 'GET',
        'callback' => 'rei_do_ape_api_orulo',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('rei-do-ape/v1', '/orulo-map', [
        'methods' => 'GET',
        'callback' => 'rei_do_ape_api_orulo_map',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('rei-do-ape/v1', '/orulo-detail', [
        'methods' => 'GET',
        'callback' => 'rei_do_ape_api_orulo_detail',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('rei-do-ape/v1', '/superbid', [
        'methods' => 'GET',
        'callback' => 'rei_do_ape_api_superbid',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('rei-do-ape/v1', '/superbid-detail', [
        'methods' => 'GET',
        'callback' => 'rei_do_ape_api_superbid_detail',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'rei_do_ape_register_apis');

require get_template_directory() . '/api/orulo.php';
require get_template_directory() . '/api/superbid.php';
