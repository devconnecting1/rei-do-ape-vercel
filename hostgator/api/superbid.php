<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sb_slug($str) {
    $str = (string)$str;
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    if ($t !== false) {
        $str = $t;
    }
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/\s+/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    $str = trim($str, '-');
    return $str;
}

function sb_fetch_html($url) {
    $headers = array(
        'Accept: text/html,application/xhtml+xml',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    );
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            throw new Exception($err ? $err : 'cURL fetch failed');
        }
        return array('status' => $status, 'body' => $body);
    }
    $ctx = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
            'follow_location' => 1,
        ),
        'ssl' => array(
            'verify_peer' => true,
            'verify_peer_name' => true,
        ),
    ));
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new Exception('fetch failed');
    }
    $status = 200;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#i', $h, $m)) {
                $status = (int)$m[1];
            }
        }
    }
    return array('status' => $status, 'body' => $body);
}

try {
    $CATEGORIES = array(
        'imoveis' => 'imoveis',
        'carros' => 'carros-motos',
        'caminhoes' => 'caminhoes-onibus',
        'maquinas-agricolas' => 'maquinas-pesadas-agricolas',
        'transporte' => 'movimentacao-transporte',
        'industrial' => 'industrial-maquinas-equipamentos',
        'animais' => 'animais',
        'tecnologia' => 'tecnologia',
        'moveis' => 'moveis-e-decoracao',
        'joias' => 'bolsas-canetas-joias-e-relogios',
        'sucatas' => 'sucatas-materiais-residuos',
    );

    $rawCat = isset($_GET['category']) ? (string)$_GET['category'] : '';
    $category = isset($CATEGORIES[$rawCat]) ? $CATEGORIES[$rawCat] : 'imoveis';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
    if ($pageSize < 1) {
        $pageSize = 10;
    }

    $url = 'https://exchange.superbid.net/categorias/' . $category . '?pageNumber=' . $page . '&pageSize=' . $pageSize . '&orderBy=score:desc';

    $resp = sb_fetch_html($url);
    if ($resp['status'] < 200 || $resp['status'] >= 300) {
        http_response_code(502);
        echo json_encode(array('error' => 'Superbid API ' . $resp['status']));
        exit;
    }

    $html = $resp['body'];
    if (!preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $match)) {
        http_response_code(502);
        echo json_encode(array('error' => 'Could not parse Superbid page'));
        exit;
    }

    $nextData = json_decode($match[1], true);
    if (!is_array($nextData)) {
        http_response_code(502);
        echo json_encode(array('error' => 'Could not parse Superbid page'));
        exit;
    }

    $offersList = isset($nextData['props']['pageProps']['offersList']) && is_array($nextData['props']['pageProps']['offersList']) ? $nextData['props']['pageProps']['offersList'] : array();
    $offers = isset($offersList['offers']) && is_array($offersList['offers']) ? $offersList['offers'] : array();
    $total = isset($offersList['total']) && $offersList['total'] ? $offersList['total'] : 0;

    $items = array();
    foreach ($offers as $o) {
        if (!is_array($o)) {
            continue;
        }
        $product = isset($o['product']) && is_array($o['product']) ? $o['product'] : array();
        $desc = isset($product['shortDesc']) && $product['shortDesc'] ? (string)$product['shortDesc'] : '';
        $thumb = '';
        if (isset($product['thumbnailUrl']) && $product['thumbnailUrl']) {
            $thumb = $product['thumbnailUrl'];
        } elseif (isset($product['galleryJson'][0]['link']) && $product['galleryJson'][0]['link']) {
            $thumb = $product['galleryJson'][0]['link'];
        }
        $photos = array();
        if (isset($product['galleryJson']) && is_array($product['galleryJson'])) {
            foreach ($product['galleryJson'] as $g) {
                if (is_array($g) && isset($g['link']) && $g['link']) {
                    $photos[] = $g['link'];
                }
            }
        }
        $storeArr = isset($o['store']) && is_array($o['store']) ? $o['store'] : array();
        $storeName = isset($storeArr['name']) && $storeArr['name'] ? $storeArr['name'] : '';
        $auctionArr = isset($o['auction']) && is_array($o['auction']) ? $o['auction'] : array();
        $auctioneer = isset($auctionArr['auctioneer']) && $auctionArr['auctioneer'] ? $auctionArr['auctioneer'] : '';
        $offerDetail = isset($o['offerDetail']) && is_array($o['offerDetail']) ? $o['offerDetail'] : array();
        $offerStatus = isset($o['offerStatus']) && is_array($o['offerStatus']) ? $o['offerStatus'] : array();
        $oid = isset($o['id']) ? $o['id'] : '';
        $items[] = array(
            'id' => 'sb-' . $oid,
            'offerId' => isset($o['id']) ? $o['id'] : null,
            'lotNumber' => isset($o['lotNumber']) ? $o['lotNumber'] : null,
            'title' => $desc,
            'price' => isset($o['price']) ? $o['price'] : null,
            'priceFormatted' => isset($o['priceFormatted']) && $o['priceFormatted'] ? $o['priceFormatted'] : '',
            'cutValue' => isset($offerDetail['cutValue']) && $offerDetail['cutValue'] ? $offerDetail['cutValue'] : null,
            'cutFormatted' => isset($offerDetail['cutFormatted']) && $offerDetail['cutFormatted'] ? $offerDetail['cutFormatted'] : '',
            'referenceValue' => isset($offerDetail['referenceValue']) && $offerDetail['referenceValue'] ? $offerDetail['referenceValue'] : null,
            'referenceFormatted' => isset($offerDetail['referenceFormatted']) && $offerDetail['referenceFormatted'] ? $offerDetail['referenceFormatted'] : '',
            'thumbnail' => $thumb,
            'photos' => $photos,
            'photoCount' => isset($product['photoCount']) && $product['photoCount'] ? $product['photoCount'] : 0,
            'store' => $storeName,
            'auctioneer' => $auctioneer,
            'endDate' => isset($o['endDate']) && $o['endDate'] ? $o['endDate'] : '',
            'sold' => isset($offerStatus['sold']) && $offerStatus['sold'] ? true : false,
            'available' => isset($offerStatus['available']) && $offerStatus['available'] ? true : false,
            'reserved' => isset($offerStatus['reserved']) && $offerStatus['reserved'] ? true : false,
            'visits' => isset($o['visits']) && $o['visits'] ? $o['visits'] : 0,
            'url' => 'https://exchange.superbid.net/oferta/' . sb_slug($desc) . '-' . $oid,
        );
    }

    header('Cache-Control: s-maxage=300, stale-while-revalidate');
    http_response_code(200);
    echo json_encode(array(
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'category' => $category,
        'categories' => array_keys($CATEGORIES),
    ));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
