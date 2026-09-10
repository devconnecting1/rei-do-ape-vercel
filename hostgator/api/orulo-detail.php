<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

try {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    if ($id === '' || $id === null) {
        http_response_code(400);
        echo json_encode(array('error' => 'id required'));
        exit;
    }

    $url = 'https://www.orulo.com.br/api/portal/buildings/' . rawurlencode((string) $id);

    $headers = array(
        'User-Agent: Mozilla/5.0',
        'Accept: application/json',
    );

    $body = false;
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        $infoStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            $body = false;
            $status = 0;
        } else {
            $status = $infoStatus;
        }
    }

    if ($body === false) {
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new Exception('Failed to fetch Orulo API');
        }
        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#i', $h, $m)) {
                    $status = (int) $m[1];
                }
            }
        }
        if ($status === 0) {
            $status = 200;
        }
    }

    if ($status < 200 || $status >= 300) {
        http_response_code($status);
        echo json_encode(array('error' => 'Orulo API error: ' . $status));
        exit;
    }

    header('Cache-Control: s-maxage=600, stale-while-revalidate');
    http_response_code(200);
    echo $body;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
