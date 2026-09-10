<?php
if (!defined('ABSPATH')) exit;

function rei_do_ape_make_slug($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    $str = trim($str, '-');
    return $str;
}

function rei_do_ape_api_superbid($request) {
    $categories = [
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
    ];

    $cat_key = sanitize_text_field($request->get_param('category') ?? 'imoveis');
    $category = $categories[$cat_key] ?? 'imoveis';
    $page = intval($request->get_param('page') ?? 1);
    $page_size = intval($request->get_param('pageSize') ?? 10);

    $url = "https://exchange.superbid.net/categorias/{$category}?pageNumber={$page}&pageSize={$page_size}&orderBy=score:desc";

    $response = wp_remote_get($url, [
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return new WP_REST_Response(['error' => 'Superbid API ' . $code], 502);
    }

    $html = wp_remote_retrieve_body($response);

    if (!preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $match)) {
        return new WP_REST_Response(['error' => 'Could not parse Superbid page'], 502);
    }

    $next_data = json_decode($match[1], true);
    $offers_list = $next_data['props']['pageProps']['offersList'] ?? null;
    $offers = $offers_list['offers'] ?? [];
    $total = $offers_list['total'] ?? 0;

    $items = [];
    foreach ($offers as $o) {
        $desc = $o['product']['shortDesc'] ?? '';
        $thumb = $o['product']['thumbnailUrl'] ?? ($o['product']['galleryJson'][0]['link'] ?? '');
        $photos = array_map(function($g) { return $g['link'] ?? ''; }, $o['product']['galleryJson'] ?? []);
        $store_name = $o['store']['name'] ?? '';
        $auctioneer = $o['auction']['auctioneer'] ?? '';

        $items[] = [
            'id' => 'sb-' . $o['id'],
            'offerId' => $o['id'],
            'lotNumber' => $o['lotNumber'] ?? '',
            'title' => $desc,
            'price' => $o['price'] ?? 0,
            'priceFormatted' => $o['priceFormatted'] ?? '',
            'cutValue' => $o['offerDetail']['cutValue'] ?? null,
            'cutFormatted' => $o['offerDetail']['cutFormatted'] ?? '',
            'referenceValue' => $o['offerDetail']['referenceValue'] ?? null,
            'referenceFormatted' => $o['offerDetail']['referenceFormatted'] ?? '',
            'thumbnail' => $thumb,
            'photos' => $photos,
            'photoCount' => $o['product']['photoCount'] ?? 0,
            'store' => $store_name,
            'auctioneer' => $auctioneer,
            'endDate' => $o['endDate'] ?? '',
            'sold' => $o['offerStatus']['sold'] ?? false,
            'available' => $o['offerStatus']['available'] ?? false,
            'reserved' => $o['offerStatus']['reserved'] ?? false,
            'visits' => $o['visits'] ?? 0,
            'url' => 'https://exchange.superbid.net/oferta/' . rei_do_ape_make_slug($desc) . '-' . $o['id'],
        ];
    }

    return new WP_REST_Response([
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'pageSize' => $page_size,
        'category' => $cat_key,
        'categories' => array_keys($categories),
    ], 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}

function rei_do_ape_fetch_superbid_page($url) {
    $response = wp_remote_get($url, [
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return [null, $response->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return [null, 'HTTP ' . $code];
    }

    $html = wp_remote_retrieve_body($response);

    if (!preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $match)) {
        return [null, 'Could not parse page'];
    }

    $next_data = json_decode($match[1], true);
    if (!is_array($next_data)) {
        return [null, 'Invalid page data'];
    }

    return [$next_data, null];
}

function rei_do_ape_superbid_detail_from_search($o) {
    $product = $o['product'] ?? [];
    $detail = $o['offerDetail'] ?? [];
    $photos = array_map(function($g) { return is_array($g) ? ($g['link'] ?? '') : ''; }, $product['galleryJson'] ?? []);
    $photos = array_values(array_filter($photos));
    $desc = $product['detailedDescription'] ?? '';
    if (!is_string($desc)) $desc = '';
    $loc = $product['location'] ?? [];
    $city = '';
    $state = '';
    if (!empty($loc['city']) && is_string($loc['city'])) {
        $parts = explode('-', $loc['city']);
        $city = trim($parts[0]);
        if (isset($parts[1])) $state = trim($parts[1]);
    }
    if (!$state && !empty($loc['state']) && is_string($loc['state'])) $state = $loc['state'];

    $result = [
        'id' => 'sb-' . $o['id'],
        'lotNumber' => $o['lotNumber'] ?? '',
        'title' => $product['shortDesc'] ?? '',
        'description' => $desc,
        'price' => $o['price'] ?? 0,
        'priceFormatted' => $o['priceFormatted'] ?? '',
        'directSaleValue' => $detail['directSaleValue'] ?? null,
        'referenceValue' => $detail['referenceValue'] ?? null,
        'reservedPrice' => $detail['reservedPrice'] ?? null,
        'photos' => $photos,
        'category' => (is_array($product['category'] ?? null) ? ($product['category']['description'] ?? '') : ($product['category'] ?? '')),
        'subcategory' => (is_array($product['subCategory'] ?? null) ? ($product['subCategory']['description'] ?? '') : ($product['subCategory'] ?? '')),
        'url' => 'https://exchange.superbid.net/oferta/' . rei_do_ape_make_slug($product['shortDesc'] ?? '') . '-' . $o['id'],
        'location' => ['city' => $city, 'state' => $state],
        'auction' => $o['auction'] ?? [],
        'properties' => [],
        'seller' => $o['seller'] ?? [],
        'status' => $o['offerStatus'] ?? [],
        'templateGroups' => [],
        'attachments' => [],
        'bids' => [
            'totalBids' => $o['totalBids'] ?? 0,
            'totalBidders' => $o['totalBidders'] ?? 0,
        ],
        'visits' => $o['visits'] ?? 0,
        'commercialCondition' => $o['commercialCondition'] ?? null,
        'groupOffer' => $o['groupOffer'] ?? null,
        'manager' => (is_array($o['manager'] ?? null) ? '' : ($o['manager'] ?? '')),
        'stores' => $o['stores'] ?? [],
    ];

    return new WP_REST_Response($result, 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}

function rei_do_ape_api_superbid_detail($request) {
    $id = sanitize_text_field($request->get_param('id'));
    if (!$id) {
        return new WP_REST_Response(['error' => 'id required'], 400);
    }

    list($next_data, $err) = rei_do_ape_fetch_superbid_page("https://exchange.superbid.net/oferta/{$id}");
    if ($err) {
        return new WP_REST_Response(['error' => $err], 502);
    }

    $offer = $next_data['props']['pageProps']['offer'] ?? null;
    $from_search = false;

    if (!$offer) {
        $candidates = $next_data['props']['pageProps']['offerDetails']['offers'] ?? [];
        $found = null;
        foreach ($candidates as $c) {
            if (strval($c['id'] ?? '') === strval($id)) {
                $found = $c;
                break;
            }
        }
        if (!$found && !empty($candidates)) {
            $found = $candidates[0];
        }
        if ($found) {
            $offer = $found;
            $from_search = true;
        }
    }

    if (!$offer) {
        return new WP_REST_Response(['error' => 'Offer not found'], 404);
    }

    if ($from_search) {
        return rei_do_ape_superbid_detail_from_search($offer);
    }

    $product = $offer['product'] ?? [];
    $location = $offer['location'] ?? [];
    $auction = $offer['auction'] ?? [];
    $props = $offer['properties'] ?? [];
    $seller = $offer['seller'] ?? [];
    $status = $offer['offerStatus'] ?? [];
    $photos = array_map(function($g) { return $g['link'] ?? ''; }, $product['galleryJson'] ?? []);

    $result = [
        'id' => 'sb-' . $offer['id'],
        'lotNumber' => $offer['lotNumber'] ?? '',
        'title' => $product['shortDesc'] ?? '',
        'description' => $product['description'] ?? '',
        'price' => $offer['price'] ?? 0,
        'priceFormatted' => $offer['priceFormatted'] ?? '',
        'directSaleValue' => $offer['directSaleValue'] ?? null,
        'referenceValue' => $offer['referenceValue'] ?? null,
        'reservedPrice' => $offer['reservedPrice'] ?? null,
        'photos' => $photos,
        'category' => $product['category'] ?? '',
        'subcategory' => $product['subcategory'] ?? '',
        'url' => 'https://exchange.superbid.net/oferta/' . rei_do_ape_make_slug($product['shortDesc'] ?? '') . '-' . $offer['id'],
        'location' => $location,
        'auction' => $auction,
        'properties' => $props,
        'seller' => $seller,
        'status' => $status,
        'templateGroups' => $offer['templateGroups'] ?? [],
        'attachments' => $offer['attachments'] ?? [],
        'bids' => $offer['bids'] ?? null,
        'visits' => $offer['visits'] ?? 0,
        'commercialCondition' => $offer['commercialCondition'] ?? null,
        'groupOffer' => $offer['groupOffer'] ?? null,
        'manager' => $offer['manager'] ?? '',
        'stores' => $offer['stores'] ?? [],
    ];

    return new WP_REST_Response($result, 200, [
        'Cache-Control' => 's-maxage=300, stale-while-revalidate',
    ]);
}
