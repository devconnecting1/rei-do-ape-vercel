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
    $id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
    if ($id === '') {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing id'));
        exit;
    }

    $url = 'https://exchange.superbid.net/oferta/-' . $id;

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

    $offerDetails = isset($nextData['props']['pageProps']['offerDetails']) && is_array($nextData['props']['pageProps']['offerDetails']) ? $nextData['props']['pageProps']['offerDetails'] : array();
    $offer = isset($offerDetails['offers'][0]) && is_array($offerDetails['offers'][0]) ? $offerDetails['offers'][0] : null;
    if ($offer === null) {
        http_response_code(404);
        echo json_encode(array('error' => 'Offer not found'));
        exit;
    }

    $product = isset($offer['product']) && is_array($offer['product']) ? $offer['product'] : array();
    $offerDet = isset($offer['offerDetail']) && is_array($offer['offerDetail']) ? $offer['offerDetail'] : array();
    $auctionArr = isset($offer['auction']) && is_array($offer['auction']) ? $offer['auction'] : array();
    $offerStatus = isset($offer['offerStatus']) && is_array($offer['offerStatus']) ? $offer['offerStatus'] : array();
    $sellerArr = isset($offer['seller']) && is_array($offer['seller']) ? $offer['seller'] : array();
    $storeArr = isset($offer['store']) && is_array($offer['store']) ? $offer['store'] : array();
    $managerArr = isset($offer['manager']) && is_array($offer['manager']) ? $offer['manager'] : array();
    $bidInc = isset($offer['currentBidIncrement']) && is_array($offer['currentBidIncrement']) ? $offer['currentBidIncrement'] : array();
    $eventPipeline = isset($offer['eventPipeline']) && is_array($offer['eventPipeline']) ? $offer['eventPipeline'] : array();
    $prodLoc = isset($product['location']) && is_array($product['location']) ? $product['location'] : array();
    $auctionAddr = isset($auctionArr['address']) && is_array($auctionArr['address']) ? $auctionArr['address'] : array();

    $photos = array();
    if (isset($product['galleryJson']) && is_array($product['galleryJson'])) {
        foreach ($product['galleryJson'] as $g) {
            if (is_array($g) && isset($g['link']) && $g['link']) {
                $photos[] = $g['link'];
            }
        }
    }
    $thumb = '';
    if (isset($product['thumbnailUrl']) && $product['thumbnailUrl']) {
        $thumb = $product['thumbnailUrl'];
    } elseif (isset($photos[0]) && $photos[0]) {
        $thumb = $photos[0];
    }

    $props = array();
    $templateGroups = array();
    $template = isset($product['template']) && is_array($product['template']) ? $product['template'] : array();
    if (isset($template['groups']) && is_array($template['groups'])) {
        foreach ($template['groups'] as $group) {
            if (!is_array($group)) {
                continue;
            }
            $groupData = array(
                'id' => isset($group['id']) && $group['id'] ? $group['id'] : '',
                'title' => isset($group['title']) && $group['title'] ? $group['title'] : '',
                'properties' => array(),
            );
            $gprops = isset($group['properties']) && is_array($group['properties']) ? $group['properties'] : array();
            foreach ($gprops as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $pid = isset($p['id']) && $p['id'] ? $p['id'] : (isset($p['title']) && $p['title'] ? $p['title'] : '');
                if (isset($p['value']) && $p['value']) {
                    $props[$pid ? $pid : ''] = $p['value'];
                }
                $groupData['properties'][] = array(
                    'id' => isset($p['id']) && $p['id'] ? $p['id'] : '',
                    'title' => isset($p['title']) && $p['title'] ? $p['title'] : (isset($p['id']) && $p['id'] ? $p['id'] : ''),
                    'value' => isset($p['value']) && $p['value'] ? $p['value'] : '',
                );
            }
            $templateGroups[] = $groupData;
        }
    }

    $subCat = isset($product['subCategory']) && is_array($product['subCategory']) ? $product['subCategory'] : array();
    $subCatTr = isset($subCat['translation']['map']['pt_BR']) && $subCat['translation']['map']['pt_BR'] ? $subCat['translation']['map']['pt_BR'] : '';
    $subcategory = $subCatTr ? $subCatTr : (isset($subCat['description']) && $subCat['description'] ? $subCat['description'] : '');
    $subCategoryId = isset($subCat['id']) && $subCat['id'] ? $subCat['id'] : null;
    $catArr = isset($subCat['category']) && is_array($subCat['category']) ? $subCat['category'] : array();
    $catTr = isset($catArr['translation']['map']['pt_BR']) && $catArr['translation']['map']['pt_BR'] ? $catArr['translation']['map']['pt_BR'] : '';
    $category = $catTr ? $catTr : (isset($catArr['description']) && $catArr['description'] ? $catArr['description'] : '');
    $categoryId = isset($catArr['id']) && $catArr['id'] ? $catArr['id'] : null;
    $ptype = isset($product['productType']) && is_array($product['productType']) ? $product['productType'] : array();
    $ptypeTr = isset($ptype['translation']['map']['pt_BR']) && $ptype['translation']['map']['pt_BR'] ? $ptype['translation']['map']['pt_BR'] : '';
    $productType = $ptypeTr ? $ptypeTr : (isset($ptype['description']) && $ptype['description'] ? $ptype['description'] : '');
    $productTypeId = isset($ptype['id']) && $ptype['id'] ? $ptype['id'] : null;

    $attachments = array();
    if (isset($product['attachments']) && is_array($product['attachments'])) {
        foreach ($product['attachments'] as $a) {
            if (!is_array($a)) {
                continue;
            }
            $aname = isset($a['originalFileName']) && $a['originalFileName'] ? $a['originalFileName'] : (isset($a['fileName']) ? $a['fileName'] : null);
            $attachments[] = array(
                'name' => $aname,
                'url' => isset($a['link']) ? $a['link'] : null,
                'type' => isset($a['contentType']) ? $a['contentType'] : null,
            );
        }
    }

    $country = isset($prodLoc['country']) && $prodLoc['country'] ? $prodLoc['country'] : (isset($auctionAddr['countryName']) && $auctionAddr['countryName'] ? $auctionAddr['countryName'] : 'Brasil');
    $location = array(
        'city' => isset($prodLoc['city']) && $prodLoc['city'] ? $prodLoc['city'] : (isset($auctionAddr['city']) && $auctionAddr['city'] ? $auctionAddr['city'] : ''),
        'state' => isset($prodLoc['state']) && $prodLoc['state'] ? $prodLoc['state'] : (isset($auctionAddr['stateCode']) && $auctionAddr['stateCode'] ? $auctionAddr['stateCode'] : ''),
        'street' => isset($auctionAddr['street']) && $auctionAddr['street'] ? $auctionAddr['street'] : '',
        'number' => isset($auctionAddr['number']) && $auctionAddr['number'] ? $auctionAddr['number'] : '',
        'district' => isset($auctionAddr['district']) && $auctionAddr['district'] ? $auctionAddr['district'] : '',
        'complement' => isset($auctionAddr['complement']) && $auctionAddr['complement'] ? $auctionAddr['complement'] : '',
        'country' => $country,
        'region' => isset($auctionAddr['regionName']) && $auctionAddr['regionName'] ? $auctionAddr['regionName'] : '',
        'geo' => isset($prodLoc['locationGeo']) && $prodLoc['locationGeo'] ? $prodLoc['locationGeo'] : null,
    );

    $subMarketplaces = array();
    if (isset($auctionArr['subMarketplaces']) && is_array($auctionArr['subMarketplaces'])) {
        foreach ($auctionArr['subMarketplaces'] as $s) {
            $subMarketplaces[] = (is_array($s) && isset($s['subMarketplaceDesc']) && $s['subMarketplaceDesc']) ? $s['subMarketplaceDesc'] : '';
        }
    }
    $stages = array();
    $rawStages = isset($eventPipeline['stages']) && is_array($eventPipeline['stages']) ? $eventPipeline['stages'] : array();
    foreach ($rawStages as $s) {
        if (!is_array($s)) {
            continue;
        }
        $stages[] = array(
            'eventId' => isset($s['eventId']) ? $s['eventId'] : null,
            'description' => isset($s['eventDesc']) && $s['eventDesc'] ? $s['eventDesc'] : '',
            'startDate' => isset($s['beginDate']) && $s['beginDate'] ? $s['beginDate'] : '',
            'endDate' => isset($s['endDate']) && $s['endDate'] ? $s['endDate'] : '',
            'initialBid' => isset($s['initialBidValue']) && $s['initialBidValue'] ? $s['initialBidValue'] : 0,
            'active' => isset($s['isActive']) && $s['isActive'] ? true : false,
        );
    }
    $auction = array(
        'modality' => isset($auctionArr['modalityDesc']) && $auctionArr['modalityDesc'] ? $auctionArr['modalityDesc'] : '',
        'startDate' => isset($auctionArr['beginDate']) && $auctionArr['beginDate'] ? $auctionArr['beginDate'] : '',
        'endDate' => isset($offer['endDate']) && $offer['endDate'] ? $offer['endDate'] : '',
        'maxEndDate' => isset($auctionArr['maxEnddateOffer']) && $auctionArr['maxEnddateOffer'] ? $auctionArr['maxEnddateOffer'] : '',
        'auctioneer' => isset($auctionArr['auctioneer']) && $auctionArr['auctioneer'] ? $auctionArr['auctioneer'] : '',
        'registry' => isset($auctionArr['registry']) && $auctionArr['registry'] ? $auctionArr['registry'] : '',
        'praca' => isset($auctionArr['judicialPracaDescription']) && $auctionArr['judicialPracaDescription'] ? $auctionArr['judicialPracaDescription'] : '',
        'subMarketplaces' => $subMarketplaces,
        'stages' => $stages,
    );

    $phones = array();
    if (isset($sellerArr['phone']) && is_array($sellerArr['phone'])) {
        foreach ($sellerArr['phone'] as $p) {
            if (!is_array($p)) {
                continue;
            }
            $ddi = isset($p['ddi']) ? $p['ddi'] : '';
            $ddd = isset($p['ddd']) ? $p['ddd'] : '';
            $num = isset($p['number']) ? $p['number'] : '';
            $phones[] = '+' . $ddi . ' (' . $ddd . ') ' . $num;
        }
    }
    $companyName = '';
    if (isset($sellerArr['company'][0]['fantasyName']) && $sellerArr['company'][0]['fantasyName']) {
        $companyName = $sellerArr['company'][0]['fantasyName'];
    }
    $seller = array(
        'name' => isset($sellerArr['name']) && $sellerArr['name'] ? $sellerArr['name'] : '',
        'city' => isset($sellerArr['city']) && $sellerArr['city'] ? $sellerArr['city'] : '',
        'phones' => $phones,
        'company' => $companyName,
    );

    $stores = array();
    if (isset($offer['stores']) && is_array($offer['stores'])) {
        foreach ($offer['stores'] as $s) {
            if (!is_array($s)) {
                continue;
            }
            $stores[] = array(
                'name' => isset($s['name']) ? $s['name'] : null,
                'logo' => isset($s['logoUri']) && $s['logoUri'] ? $s['logoUri'] : '',
            );
        }
    }

    $shortDesc = isset($product['shortDesc']) && $product['shortDesc'] ? (string)$product['shortDesc'] : '';
    $offerDescArr = isset($offer['offerDescription']) && is_array($offer['offerDescription']) ? $offer['offerDescription'] : array();
    $description = isset($product['detailedDescription']) && $product['detailedDescription'] ? $product['detailedDescription'] : (isset($offerDescArr['offerDescription']) && $offerDescArr['offerDescription'] ? $offerDescArr['offerDescription'] : '');
    $oid = isset($offer['id']) ? $offer['id'] : $id;

    $result = array(
        'id' => 'sb-' . $oid,
        'offerId' => isset($offer['id']) ? $offer['id'] : null,
        'lotNumber' => isset($offer['lotNumber']) ? $offer['lotNumber'] : null,
        'title' => $shortDesc,
        'price' => isset($offer['price']) ? $offer['price'] : null,
        'priceFormatted' => isset($offer['priceFormatted']) && $offer['priceFormatted'] ? $offer['priceFormatted'] : '',
        'initialBid' => isset($offerDet['initialBidValue']) && $offerDet['initialBidValue'] ? $offerDet['initialBidValue'] : null,
        'initialBidFormatted' => isset($offerDet['initialBidValueFormatted']) && $offerDet['initialBidValueFormatted'] ? $offerDet['initialBidValueFormatted'] : '',
        'directSaleValue' => isset($offerDet['directSaleValue']) && $offerDet['directSaleValue'] ? $offerDet['directSaleValue'] : null,
        'directSaleValueFormatted' => isset($offerDet['directSaleValueFormatted']) && $offerDet['directSaleValueFormatted'] ? $offerDet['directSaleValueFormatted'] : '',
        'referenceValue' => isset($offerDet['referenceValue']) && $offerDet['referenceValue'] ? $offerDet['referenceValue'] : null,
        'referenceValueFormatted' => isset($offerDet['referenceValueFormatted']) && $offerDet['referenceValueFormatted'] ? $offerDet['referenceValueFormatted'] : '',
        'cutValue' => isset($offerDet['cutValue']) && $offerDet['cutValue'] ? $offerDet['cutValue'] : null,
        'cutValueFormatted' => isset($offerDet['cutValueFormatted']) && $offerDet['cutValueFormatted'] ? $offerDet['cutValueFormatted'] : '',
        'reservedPrice' => isset($offerDet['reservedPrice']) && $offerDet['reservedPrice'] ? $offerDet['reservedPrice'] : null,
        'reservedFormatted' => isset($offerDet['reservedPriceFormatted']) && $offerDet['reservedPriceFormatted'] ? $offerDet['reservedPriceFormatted'] : '',
        'currentMinBid' => isset($offerDet['currentMinBid']) && $offerDet['currentMinBid'] ? $offerDet['currentMinBid'] : null,
        'currentMinBidFormatted' => isset($offerDet['currentMinBidFormatted']) && $offerDet['currentMinBidFormatted'] ? $offerDet['currentMinBidFormatted'] : '',
        'currentMaxBid' => isset($offerDet['currentMaxBid']) && $offerDet['currentMaxBid'] ? $offerDet['currentMaxBid'] : null,
        'currentMaxBidFormatted' => isset($offerDet['currentMaxBidFormatted']) && $offerDet['currentMaxBidFormatted'] ? $offerDet['currentMaxBidFormatted'] : '',
        'thumbnail' => $thumb,
        'photos' => $photos,
        'photoCount' => isset($product['photoCount']) && $product['photoCount'] ? $product['photoCount'] : 0,
        'videoCount' => isset($product['videoUrlCount']) && $product['videoUrlCount'] ? $product['videoUrlCount'] : 0,
        'description' => $description,
        'properties' => $props,
        'templateGroups' => $templateGroups,
        'subcategory' => $subcategory,
        'subCategoryId' => $subCategoryId,
        'category' => $category,
        'categoryId' => $categoryId,
        'productType' => $productType,
        'productTypeId' => $productTypeId,
        'attachments' => $attachments,
        'location' => $location,
        'auction' => $auction,
        'seller' => $seller,
        'stores' => $stores,
        'store' => array(
            'name' => isset($storeArr['name']) && $storeArr['name'] ? $storeArr['name'] : '',
            'logo' => isset($storeArr['logoUri']) && $storeArr['logoUri'] ? $storeArr['logoUri'] : '',
        ),
        'manager' => isset($managerArr['name']) && $managerArr['name'] ? $managerArr['name'] : '',
        'commercialCondition' => isset($offer['commercialCondition']) ? $offer['commercialCondition'] : null,
        'groupOffer' => isset($offer['groupOffer']) ? $offer['groupOffer'] : null,
        'status' => array(
            'sold' => isset($offerStatus['sold']) && $offerStatus['sold'] ? true : false,
            'reserved' => isset($offerStatus['reserved']) && $offerStatus['reserved'] ? true : false,
            'available' => isset($offerStatus['available']) && $offerStatus['available'] ? true : false,
            'closed' => isset($offerStatus['closed']) && $offerStatus['closed'] ? true : false,
            'giveYourBid' => isset($offerStatus['giveYourBid']) && $offerStatus['giveYourBid'] ? true : false,
        ),
        'bids' => array(
            'totalBids' => isset($offer['totalBids']) && $offer['totalBids'] ? $offer['totalBids'] : 0,
            'totalBidders' => isset($offer['totalBidders']) && $offer['totalBidders'] ? $offer['totalBidders'] : 0,
            'increment' => isset($bidInc['currentBidIncrement']) && $bidInc['currentBidIncrement'] ? $bidInc['currentBidIncrement'] : 0,
            'incrementFormatted' => isset($bidInc['currentBidIncrementFormatted']) && $bidInc['currentBidIncrementFormatted'] ? $bidInc['currentBidIncrementFormatted'] : '',
        ),
        'visits' => isset($offer['visits']) && $offer['visits'] ? $offer['visits'] : 0,
        'url' => 'https://exchange.superbid.net/oferta/' . sb_slug($shortDesc ? $shortDesc : 'oferta') . '-' . $oid,
    );

    header('Cache-Control: s-maxage=300, stale-while-revalidate');
    http_response_code(200);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
