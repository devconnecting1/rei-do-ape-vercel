<?php
if (!defined('ABSPATH')) exit;

function rei_do_ape_api_orulo($request) {
    $params = $request->get_params();
    $page = intval($params['page'] ?? 1);
    $per_page = intval($params['results_per_page'] ?? 10);

    $query = [
        'page' => $page,
        'results_per_page' => $per_page,
    ];
    if (!empty($params['state'])) $query['state'] = $params['state'];
    if (!empty($params['city'])) $query['city'] = $params['city'];
    if (!empty($params['min_price'])) $query['min_price'] = $params['min_price'];
    if (!empty($params['max_price'])) $query['max_price'] = $params['max_price'];
    if (!empty($params['min_area'])) $query['min_private_area'] = $params['min_area'];
    if (!empty($params['max_area'])) $query['max_private_area'] = $params['max_area'];
    if (!empty($params['bedrooms'])) $query['bedrooms[]'] = $params['bedrooms'];
    if (!empty($params['q'])) $query['name'] = $params['q'];

    $url = 'https://www.orulo.com.br/api/portal/v2/buildings?' . http_build_query($query);

    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code !== 200) {
        return new WP_REST_Response(['error' => 'Orulo API error: ' . $code], $code);
    }

    $data = json_decode($body, true);
    return new WP_REST_Response($data, 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}

function rei_do_ape_api_orulo_map($request) {
    $params = $request->get_params();

    $query = [
        'total_pages' => $params['total_pages'] ?? 10,
    ];
    if (!empty($params['state'])) $query['state'] = $params['state'];
    if (!empty($params['bedrooms'])) $query['bedrooms[]'] = $params['bedrooms'];
    if (!empty($params['q'])) $query['name'] = $params['q'];

    $url = 'https://www.orulo.com.br/api/portal/v2/buildings?' . http_build_query($query);

    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $markers = [];
    if (!empty($data['buildings'])) {
        foreach ($data['buildings'] as $b) {
            $lat = $b['address']['latitude'] ?? ($b['address']['lat'] ?? null);
            $lng = $b['address']['longitude'] ?? ($b['address']['lng'] ?? null);
            if (!empty($lat) && !empty($lng)) {
                $markers[] = [
                    'id' => $b['id'],
                    'name' => $b['name'] ?? '',
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                    'price' => $b['min_price'] ?? 0,
                ];
            }
        }
    }

    return new WP_REST_Response(['markers' => $markers], 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}

function rei_do_ape_api_orulo_detail($request) {
    $id = sanitize_text_field($request->get_param('id'));
    if (!$id) {
        return new WP_REST_Response(['error' => 'id required'], 400);
    }

    $url = 'https://www.orulo.com.br/api/portal/v2/buildings/' . $id;

    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code !== 200) {
        return new WP_REST_Response(['error' => 'Not found'], $code);
    }

    $data = json_decode($body, true);
    return new WP_REST_Response($data, 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}
