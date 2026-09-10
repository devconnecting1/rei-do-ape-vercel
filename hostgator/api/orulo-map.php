<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

try {
    $state = isset($_GET['state']) ? $_GET['state'] : '';
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    $bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
    $q = isset($_GET['q']) ? $_GET['q'] : '';

    $total_pages_raw = isset($_GET['total_pages']) ? $_GET['total_pages'] : 2;
    $pages = (int) $total_pages_raw;
    if (!$pages) {
        $pages = 2;
    }
    if ($pages > 20) {
        $pages = 20;
    }
    if ($pages < 1) {
        $pages = 1;
    }

    $urls = array();
    for ($p = 1; $p <= $pages; $p++) {
        $params = array();
        $params['page'] = (string) $p;
        $params['results_per_page'] = '50';
        if ($state !== '' && $state !== null) {
            $params['state'] = $state;
        }
        if ($city !== '' && $city !== null) {
            $params['city'] = $city;
        }
        if ($bedrooms !== '' && $bedrooms !== null) {
            $params['bedrooms[]'] = $bedrooms;
        }
        if ($q !== '' && $q !== null) {
            $params['name'] = $q;
        }
        $urls[] = 'https://www.orulo.com.br/api/portal/v2/buildings?' . http_build_query($params);
    }

    $responses = array();
    foreach ($urls as $u) {
        $responses[] = null;
    }

    if (function_exists('curl_multi_init')) {
        $mh = curl_multi_init();
        $handles = array();
        foreach ($urls as $i => $u) {
            $ch = curl_init($u);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'User-Agent: Mozilla/5.0',
                'Accept: application/json',
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        $running = null;
        do {
            $mrc = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $mrc === CURLM_OK);

        foreach ($handles as $i => $ch) {
            $body = curl_multi_getcontent($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($body === false || $body === '' || $httpCode < 200 || $httpCode >= 300) {
                $responses[$i] = array('buildings' => array());
            } else {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    $responses[$i] = array('buildings' => array());
                } else {
                    $responses[$i] = $decoded;
                }
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    } else {
        foreach ($urls as $i => $u) {
            $ctx = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
                    'timeout' => 12,
                    'ignore_errors' => true,
                ),
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ),
            ));
            $body = @file_get_contents($u, false, $ctx);
            if ($body === false || $body === '') {
                $responses[$i] = array('buildings' => array());
                continue;
            }
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                $responses[$i] = array('buildings' => array());
            } else {
                $responses[$i] = $decoded;
            }
        }
    }

    $markers = array();
    foreach ($responses as $data) {
        $buildings = array();
        if (is_array($data) && isset($data['buildings']) && is_array($data['buildings'])) {
            $buildings = $data['buildings'];
        }
        foreach ($buildings as $b) {
            if (!is_array($b) || !isset($b['address']) || !is_array($b['address'])) {
                continue;
            }
            $lat = isset($b['address']['latitude']) ? $b['address']['latitude'] : null;
            $lng = isset($b['address']['longitude']) ? $b['address']['longitude'] : null;
            if (empty($lat) || empty($lng)) {
                continue;
            }
            $price = isset($b['min_price']) && $b['min_price'] ? $b['min_price'] : 0;
            $markers[] = array(
                'id' => isset($b['id']) ? $b['id'] : null,
                'name' => isset($b['name']) ? $b['name'] : null,
                'price' => $price,
                'lat' => $lat,
                'lng' => $lng,
            );
        }
    }

    header('Cache-Control: s-maxage=600, stale-while-revalidate');
    http_response_code(200);
    echo json_encode(array('markers' => $markers, 'total' => count($markers)));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
