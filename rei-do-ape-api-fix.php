<?php
/*
Plugin Name: Rei do Ape API Fix (MU)
Description: Registra as rotas rei-do-ape/v1 independente do tema ativo + diagnostico em debug.log
Version: 1.0.0
*/
if (!defined('ABSPATH')) exit;

error_log('[rei-do-ape-mu] stylesheet ativo: ' . get_option('stylesheet') . ' | template: ' . get_option('template'));

$base = WP_CONTENT_DIR . '/themes/rei-do-ape/api/';
error_log('[rei-do-ape-mu] orulo.php existe: ' . var_export(file_exists($base . 'orulo.php'), true));
error_log('[rei-do-ape-mu] superbid.php existe: ' . var_export(file_exists($base . 'superbid.php'), true));

if (file_exists($base . 'orulo.php')) require_once $base . 'orulo.php';
if (file_exists($base . 'superbid.php')) require_once $base . 'superbid.php';

add_action('rest_api_init', function () {
    if (!function_exists('register_rest_route')) return;
    $routes = [
        '/orulo' => 'rei_do_ape_api_orulo',
        '/orulo-map' => 'rei_do_ape_api_orulo_map',
        '/orulo-detail' => 'rei_do_ape_api_orulo_detail',
        '/superbid' => 'rei_do_ape_api_superbid',
        '/superbid-detail' => 'rei_do_ape_api_superbid_detail',
    ];
    foreach ($routes as $route => $cb) {
        if (is_callable($cb)) {
            register_rest_route('rei-do-ape/v1', $route, [
                'methods' => 'GET',
                'callback' => $cb,
                'permission_callback' => '__return_true',
            ]);
        } else {
            error_log('[rei-do-ape-mu] callback ausente: ' . $cb);
        }
    }
});
