
<?php

namespace App\Service;
// app/Service/WebSearch.php

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class WebSearch
{

    function google_suggestion($request)
    {
        $xml = $this->html('http://www.google.com/complete/search', [
            'hl'     => 'ja',
            'output' => 'toolbar',
            'ie'     => 'utf_8',
            'oe'     => 'utf_8',
            'q'      => $request,
        ]);
        return simplexml_load_string($xml);
    }

    function google_search($request)
    {
        $search_result = $this->html('https://www.googleapis.com/customsearch/v1', [
            'key' => 'AIzaSyDcfq29_M_8cLkyi9GMzM3TriH51_irHFg',
            'cx'  => '014862834445827578797:uw7zogs5grs',
            'num' => 3,
            'q'   => $request,
        ]);

        $search_result = json_decode($search_result);
        $res = [];
        if (isset($search_result->items) && $search_result->items) {
            foreach ($search_result->items as $key1 => $value1) {
                $res['url'][] = $value1->link;
                $crawler = Goutte::request('GET', $value1->link);
                $crawler->filter('h2')->each(function ($node) use ($key1, $res) {
                    if ($node->filter('img')->attr("src")) {
                        $res[$key1][] = $node->text() . "（画像）";
                    } else {
                        $res[$key1][] = $node->text();
                    }
                });
            }
        }
        return $res;
    }

    function amazon_search($request, $page = 1)
    {

        switch (service('type')) {
            case 'bestranking':
                $Associate_tag = 'excite-can-22';
                break;
            case 'gooranking':
                $Associate_tag = 'goo-ranking-22';
                break;
            case 'osusumeis':
                $Associate_tag = 'kaeln03-22';
                break;
            default:
                $Associate_tag = 'candle087-22';
                break;
        }

        $serviceName = "ProductAdvertisingAPI";
        $region = "us-west-2";
        $accessKey = "AKIAJJC5HXAOYF6GHOMQ";
        $secretKey = "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";
        $payload = "{"
            . " \"Keywords\": \"$request\","
            . " \"Resources\": ["
            . "  \"BrowseNodeInfo.BrowseNodes\","
            . "  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
            . "  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
            . "  \"BrowseNodeInfo.WebsiteSalesRank\","
            . "  \"CustomerReviews.Count\","
            . "  \"CustomerReviews.StarRating\","
            . "  \"Images.Primary.Small\","
            . "  \"Images.Primary.Medium\","
            . "  \"Images.Primary.Large\","
            . "  \"Images.Variants.Small\","
            . "  \"Images.Variants.Medium\","
            . "  \"Images.Variants.Large\","
            . "  \"ItemInfo.ByLineInfo\","
            . "  \"ItemInfo.ContentInfo\","
            . "  \"ItemInfo.ContentRating\","
            . "  \"ItemInfo.Classifications\","
            . "  \"ItemInfo.ExternalIds\","
            . "  \"ItemInfo.Features\","
            . "  \"ItemInfo.ManufactureInfo\","
            . "  \"ItemInfo.ProductInfo\","
            . "  \"ItemInfo.TechnicalInfo\","
            . "  \"ItemInfo.Title\","
            . "  \"ItemInfo.TradeInInfo\","
            . "  \"Offers.Summaries.HighestPrice\","
            . "  \"Offers.Summaries.LowestPrice\","
            . "  \"ParentASIN\","

            . "  \"SearchRefinements\""
            . " ],"
            . " \"PartnerTag\": \"$Associate_tag\","
            . " \"PartnerType\": \"Associates\","
            . " \"Marketplace\": \"www.amazon.co.jp\""
            . "}";
        $host = "webservices.amazon.co.jp";
        $uriPath = "/paapi5/searchitems";
        $awsv4 = new AwsV4($accessKey, $secretKey);
        $awsv4->setRegionName($region);
        $awsv4->setServiceName($serviceName);
        $awsv4->setPath($uriPath);
        $awsv4->setPayload($payload);
        $awsv4->setRequestMethod("POST");
        $awsv4->addHeader('content-encoding', 'amz-1.0');
        $awsv4->addHeader('content-type', 'application/json; charset=utf-8');
        $awsv4->addHeader('host', $host);
        $awsv4->addHeader('x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems');
        $headers = $awsv4->getHeaders();
        $headerString = "";
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        $params = array(
            'http' => array(
                'header' => $headerString,
                'method' => 'POST',
                'content' => $payload
            )
        );
        $stream = stream_context_create($params);
        $fp = @fopen('https://' . $host . $uriPath, 'rb', false, $stream);


        if (!$fp) {
            throw new Exception("Exception Occured");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new Exception("Exception Occured");
        }

        $array = json_decode($response, true);
        $obj = $array['SearchResult']['Items'];
        $results = [];

        $data = $obj;
        if (!array_key_exists(0, $data)) {
            $results[] = $this->_amazon_results($data);
        } else {
            foreach ($data as $key => $val) {
                $results[] = $this->_amazon_results($val);
            }
        }
        $hits = count($results);

        $limit = 10;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }
        return [
            "results" => $results,
            "meta"    => [
                "page"     => $page,
                "limit"    => $limit,
                "nextpage" => $nextpage,
                "hits"     => $hits,
            ],
        ];
    }

    public function _amazon_results($val)
    {
        $one_product_images = [];
        if (isset($val['Images']['Primary']['Large']['URL'])) {
            if (gettype($val['Images']['Primary']['Large']['URL']) == 'string') {
                $img_url = $val['Images']['Primary']['Large']['URL'];
            } else {
                $img_url = "https://image.knowsia.jp/common/noimage.png";
            }
        } else {
            $img_url = "https://image.knowsia.jp/common/noimage.png";
        }
        if (isset($val['Images']['Variants'])) {
            foreach ($val['Images']['Variants'] as  $related_images) {
                if (isset($related_images['Large']['URL'])) {
                    $img_sub_url = $related_images['Large']['URL'];
                } else {
                    $img_sub_url = "https://image.knowsia.jp/common/noimage.png";
                }
                $one_product_images[] = $img_sub_url;
            }
        }
        if (isset($val['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'])) {
            $manufacturer = $val['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'];
        } else {
            $manufacturer = "";
        }
        if (isset($val['Offers']['Summaries'][0]['LowestPrice']['Amount'])) {
            $price = $val['Offers']['Summaries'][0]['LowestPrice']['Amount'];
        } else {
            $price = "";
        }
        if (isset($val['ItemAttributes']['Feature'])) {
            $feature = $val['ItemAttributes']['Feature'];
        } else {
            $feature = "";
        }
        if (isset($val['EditorialReviews']['EditorialReview']['Content'])) {
            $editorialReview = $val['EditorialReviews']['EditorialReview']['Content'];
        } else {
            $editorialReview = "";
        }

        preg_match('/\/dp\/.*/', $val['DetailPageURL'], $match);
        $modifiedurl = "https://www.amazon.co.jp" . $match[0];
        return [
            "scrape_img"     => $img_url,
            "scrape_sub_img" => $one_product_images,
            "scrape_url"     => $modifiedurl,
            "scrape_title"   => $val['ItemInfo']['Title']['DisplayValue'],
            "manufacturer"   => $manufacturer,
            "price"          => $price,
            "feature"        => $feature,
            "editorialReview" => $editorialReview,
        ];
    }

    function rakuten_search($request)
    {
        $affiliate_id = '17b592bb.218bc1d1.17b592bd.70a9cb04';
        $app_id = '1007195612469189352';
        switch (service('type')) {
            case 'bestranking':
                $RTfixed_id = '_RTcand00000001';
                break;
            case 'gooranking':
                $RTfixed_id = '_RTcand00000002';
                break;
            case 'osusumeis':
                $RTfixed_id = '_RTcand00000004';
                break;
            case 'kogunotatsujin':
                $RTfixed_id = '_RTcand00000005';
                break;
            default:
                $RTfixed_id = '_RTcand00000001';
                break;
        }
        $baseurl = 'https://hb.afl.rakuten.co.jp/hgc/' . $affiliate_id . '/' . $RTfixed_id;

        $search_url = 'https://search.rakuten.co.jp/search/mall/' . urlencode($request) . '/';

        $link_url = $baseurl . '?pc=' . urlencode($search_url) . '&m=' . urlencode($search_url);

        $rakuten = 'https://app.rakuten.co.jp/services/api/IchibaItem/Search/20170706?format=json&keyword=' . urlencode($request) . '&applicationId=' . $app_id;
        $response = file_get_contents($rakuten);
        $array = json_decode($response, TRUE);

        $obj = $array['Items'];
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            if (!isset($obj[$i]['Item'])) break;
            $item = $obj[$i]['Item'];
            $affiliate_url = $baseurl . '?pc=' . urlencode($item['itemUrl']) . '&m=' . urlencode($item['itemUrl']);
            $item_array = [
                "scrape_img"   => $item['mediumImageUrls'][0]['imageUrl'],
                "scrape_url"   => $affiliate_url,
                "scrape_title" => $item['itemName'],
                "manufacturer" => $item['shopName'],
                "price"        => $item['itemPrice'],
            ];
            $results[] = $item_array;
        }

        return ["linkurl" => $link_url, "results" => $results];
    }

    function yahoo_search($request)
    {
        $app_id = 'dj00aiZpPUtXYnpUdnBVRlk4eSZzPWNvbnN1bWVyc2VjcmV0Jng9YzI-';
        switch (service('type')) {
            case 'bestranking':
                $affiliate_sid = '3437865';
                $affiliate_pid = '885619230';
                break;
            case 'gooranking':
                $affiliate_sid = '3447015';
                $affiliate_pid = '885732032';
                break;
            case 'osusumeis':
                $affiliate_sid = '3467673';
                $affiliate_pid = '885906301';
                break;
            default:
                $affiliate_sid = '3437865';
                $affiliate_pid = '885619230';
                break;
        }
        $baseurl = 'https://ck.jp.ap.valuecommerce.com/servlet/referral?sid=' . $affiliate_sid . '&pid=' . $affiliate_pid . '&vc_url=';
        $search_url = 'https://shopping.yahoo.co.jp/search?first=1&tab_ex=commerce&fr=shp-prop&oq=&aq=&mcr=70f20422e7ebf967499eba4db9609fd4&ts=1549946450&p=' . urlencode($request) . '&pf=&pt=&sc_i=shp_pc_top_searchBox&sretry=0';
        $link_url = $baseurl . urlencode($search_url);

        $yahoo_item_search_url = 'http://shopping.yahooapis.jp/ShoppingWebService/V1/json/itemSearch?appid=' . $app_id . '&query=' . urlencode($request);
        $response = file_get_contents($yahoo_item_search_url);
        $array = json_decode($response, TRUE);

        $yahoo_results = $array['ResultSet'][0]['Result'];

        $results = [];

        for ($i = 0; $i < 10; $i++) {
            if (!isset($yahoo_results[$i])) break;
            $item = $yahoo_results[$i];
            $results[] = [
                "scrape_img" => $item['Image']['Medium'],
                "scrape_url" => $baseurl . urlencode($item['Url']),
                "scrape_title" => $item['Name'],
                "manufacturer" => $item['Store']['Name'],
                "price" => $item['PriceLabel']['PremiumPrice'],
            ];
        }
        return ["linkurl" => $link_url, "results" => $results];
    }

    /**
     * @param string $request 画像フォームから送られてきた検索項目。
     * @return array フロントに渡す配列
     */
    function lazada_search($request)
    {
        require 'vendor/php-webdriver/webdriver/lib/lazadaSearch.php';
        $lazada_results = \Facebook\WebDriver\lazada::lazadaSearch($request);

        $link_url = "https://www.lazada.com.ph/catalog/?q=$request";
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            if (!isset($lazada_results[$i])) break;
            $item = $lazada_results[$i];
            $results[] = [
                "scrape_img" => $item['scrape_img'],
                "scrape_url" => $item['scrape_url'],
                "scrape_title" => $item['scrape_title'],
                "display_title" => '',
            ];
        }
        return ["linkurl" => $link_url, "results" => $results];
    }

    static function html($url, $params = [])
    {
        $url = $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        $html = @file_get_contents($url);
        mb_language("Japanese");
        return strlen($html) ? mb_convert_encoding($html, 'UTF-8', 'auto') : '';
    }
    public function shoplist($query, $page = 1)
    {
        $limit = 90;
        $url = "http://shop-list.com/women/svc/product/Search/?keyword=" . mb_convert_encoding($query, "SJIS", "auto") . "&disp=1&limit=" . $limit . "&excludeAggregate=1&page=" . $page;
        $html = file_get_contents($url);
        $html = mb_convert_encoding($html, 'utf8', 'sjis');
        $html = preg_replace('/(\n|\r|\t)/', '', $html);
        $pattern = '|<li class="listProduct_item"><a href="([^"]+)"[^>]+><img[^>]+alt="([^"]+)" data-src="([^"]+)"|';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $results = [];
        foreach ($matches as $match) {
            $results[] = [
                'scrape_url' => $match[1],
                'scrape_img' => $match[3],
                'big_scrape_img' => preg_replace('|__basethum240__|', '__basethum900__', $match[3]),
                'scrape_title' => $match[2],
                'display_title' => $this->convert_title($match[2], 20)
            ];
        }
        $hits = count($results);
        $nextpage = ($hits == $limit);

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }
    public function cubki($query, $page = 1)
    {
        $limit = 90;
        $url = "https://cubki.jp/snaps/search.json?query=" . urlencode($query) . "&page=" . $page . "&per_page=" . $limit;
        $json = file_get_contents($url);
        $obj = json_decode($json, true);
        $results = [];
        foreach ($obj["snaps"] as $e) {
            if (isset($e["pictures"][0]["sp_standard"]["url"])) {
                $results[] = [
                    'scrape_url' => $e["url"],
                    'scrape_img' => $e["pictures"][0]["sp_standard"]["url"],
                    'scrape_title' => $e["title"],
                    'display_title' => $e["title"]
                ];
            }
        }
        $hits = count($results);
        $nextpage = ($hits == $limit);

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }
    public function flickrapi($keyword, $page = 1)
    {
        $app_key = 'f6645a382b8b08a2d0772a936a620146';
        $app_secret = '2c64539a93da2735';
        $flickr = new phpFlickr($app_key, $app_secret);
        $count = 90;
        $option = [
            'text' => $keyword,
            'per_page' => $count,
            'extras' => 'url_q,url_z',
            'safe_search' => 1,
            'content_type' => 4,
            'page' => $page
        ];
        $results = $flickr->photos_search($option);
        $nextpage = (count($results) == $count);
        return [
            'results' => $results,
            'nextpage' => $nextpage,
        ];
    }
    public function pixabay($query, $page = 1)
    {
        $url = "https://pixabay.com/en/photos/?q=" . urlencode($query) . "&image_type=&cat=&min_width=&min_height=&pagi=" . $page;
        $html = file_get_contents($url);
        $html = preg_replace('/(\n|\r|\t)/', '', $html);
        $pattern1 = '|<a href="([^"]+)"><img srcset="[^"]+" src="([^"]+)"|';
        preg_match_all($pattern1, $html, $matches_1, PREG_SET_ORDER);
        $pattern2 = '|<a href="([^"]+)"><img src="[^"]+" data-lazy-srcset="[^"]+" data-lazy="([^"]+)"|';
        preg_match_all($pattern2, $html, $matches_2, PREG_SET_ORDER);
        $matches = array_merge($matches_1, $matches_2);
        $results = [];
        foreach ($matches as $match) {
            $results[] = [
                'scrape_url' => "https://pixabay.com" . $match[1],
                'scrape_img' => $match[2],
                'scrape_title' => "",
                'display_title' => ""
            ];
        }
        $hits = count($results);
        $limit = 100;
        $nextpage = ($hits == $limit);

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits
            ],
        ];
    }
    public function gurunavi($query, $page = 1, $object)
    {
        $results = [];

        $uri   = "http://api.gnavi.co.jp/RestSearchAPI/20150630/";
        $acckey = "0d56b2bd507632d5895552aa5811d173";
        $format = "json";
        $limit = 30;
        $freeword_condition = 1;
        $url  = sprintf("%s%s%s%s%s%s%s%s%s%s%s", $uri, "?format=", $format, "&keyid=", $acckey, "&offset_page=", $page, "&hit_per_page=", $limit, "&freeword=", $query);
        $json = file_get_contents($url);
        $obj = json_decode($json, true);

        if (array_key_exists('error', $obj)) {
            $hits = 0;
        } else {
            $data = $obj["rest"];
            if (!array_key_exists('0', $data)) {
                $results[] = $this->gurunavi_result($data);
            } else {
                foreach ($data as $rest) {
                    $results[] = $this->gurunavi_result($rest);
                }
            }
            $hits = count($results);
        }
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }
        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }

    public function gurunavi_result($data)
    {
        if (gettype($data['image_url']['shop_image1']) == 'string') {
            $img_url = $data['image_url']['shop_image1'];
        } else {
            if (gettype($data['image_url']['shop_image2']) == 'string') {
                $img_url = $data['image_url']['shop_image2'];
            } else {
                $img_url = "";
            }
        }
        $display_title = $this->convert_title($data['name'], 20);
        $station_exit = $this->check_api_data($data['access']['station_exit'], 'string');
        $station = $this->check_api_data($data['access']['station'], 'string');
        $walk = $this->check_api_data($data['access']['walk'], 'string');
        $line = $this->check_api_data($data['access']['line'], 'string');
        if ($station == "") {
            $access = "";
        } else {
            if ($walk == "") {
                $access = "";
            } else {
                $access = $line . $station . $station_exit . 'から徒歩' . $walk . '分';
            }
        }
        $comment_access = $this->check_api_item($access, 'string', 'アクセス');
        $holiday = preg_replace('/<BR>/', ' ', $this->check_api_data($data['holiday'], 'string'));
        $holiday = $this->check_api_item($holiday, 'string', '定休日');
        $opentime = preg_replace('/<BR>/', '\n　　　　　　', $this->check_api_data($data['opentime'], 'string'));
        $opentime = $this->check_api_item($opentime, 'string', '営業時間');
        $name = $this->check_api_item($data['name'], 'string', '名前');
        $address = $this->check_api_data($data['address'], 'string');
        $comment_address = $this->check_api_item($address, 'string', '住所');
        $tel = $this->check_api_data($data['tel'], 'string');
        $comment_tel = $this->check_api_item($tel, 'string', '電話番号');
        $url = $this->check_api_item($data['url'], 'string', '公式サイトURL');
        $comment = '■ 基本情報\n' . $name . $comment_address . $comment_access . $opentime . $holiday . $comment_tel . $url;
        $budget = $this->check_api_data($data['budget'], 'string');
        $budget = $budget == "" ? "" : $budget . '円';
        return [
            'scrape_img' => $img_url,
            'scrape_url' => $data['url'],
            'scrape_title' => $data['name'],
            'display_title' => $display_title,
            'comment' => $comment,
            'address' => $address,
            'tel' => $tel,
            'budget' => $budget,
            'access' => $access
        ];
    }
    public function instagram2($user_id)
    {
        $results = [];
        $instagram_url = 'https://www.instagram.com/' . $user_id . '/';
        $instagram_html = file_get_contents($instagram_url);
        preg_match('/<script type="text\/javascript">window\._sharedData.+?<\/script>/s', $instagram_html, $match_script);
        preg_match_all('/"(http.*?)"/', $match_script[0], $match_img);
        foreach ($match_img[1] as $value) {
            $results[] = [
                'scrape_img' => $value,
                'scrape_url' => '',
                'scrape_title' => '',
                'display_title' => ''
            ];
        }
        return [
            'results' => $results,
            'meta' => [
                'page' => 1,
                'limit' => 10,
                'nextpage' => false,
                'hits' => count($results),
            ],
        ];
    }
    public function instagram($query, $page = 1)
    {

        $tag = $query;
        $next_url = $page;
        $page_count = $page;
        $access_token = '1940625655.8a1dda3.6434210a5b0040678489ae84e11b78ea';
        $limit = 10;

        if ($next_url == 1) {
            $request_url = 'https://api.instagram.com/v1/tags/' . rawurlencode($tag) . '/media/recent?access_token=' . $access_token . '&count=' . $limit;
        } else {
            $request_url = $next_url;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $res1 = curl_exec($curl);
        $res2 = curl_getinfo($curl);
        curl_close($curl);

        $json = substr($res1, $res2['header_size']);
        $header = substr($res1, 0, $res2['header_size']);

        $obj = json_decode($json);

        if (!$obj || !isset($obj->data)) {
            return [];
        }

        $data = $obj->data;
        $results = [];
        if (array_key_exists(0, $data)) {
            foreach ($data as $val) {
                $results[] = [
                    'scrape_img' => $val->images->standard_resolution->url,
                    'scrape_url' => $val->images->standard_resolution->url,
                    'scrape_title' => $val->caption->text,
                    'display_title' => $this->convert_title($val->caption->text, 20)
                ];
            }
        }
        $hits = count($results);
        if (isset($obj->pagination->next_url)) {
            $nextpage = true;
            $page = $obj->pagination->next_url;
        } else {
            $nextpage = false;
            $page = "";
        }
        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ]
        ];
    }

    public function wear($query, $page = 1)
    {
        $url = "http://wear.jp/item/?search_word=" . $query . "&pageno=" . $page;

        $html = file_get_contents($url);

        $html = preg_replace('/(\n|\r|\t)/', '', $html);
        $pattern = "/<li class=\"like_mark\">.+?<a href=\"(.+?)\".+?(http:.+?jpg).+?<!-- \/.item_coordinate -->/";
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $results = [];
        foreach ($matches as $match) {
            $results[] = [
                'scrape_url' => "http://wear.jp" . $match[1],
                'scrape_img' => $match[2]
            ];
        }
        $hits = count($results);
        $limit = 30;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }
        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }

    public function hair($query, $page = 1)
    {
        $url = "https://tiful.jp/stylist/?s=" . urlencode($query) . "&page=" . $page;
        $html = file_get_contents($url);
        $html = preg_replace('/(\n|\r|\t)/', '', $html);
        $pattern = "/<section class=\"sec sec-post sec-archive clearfix\"><a href=\"(.+?)\"><img.+?src=\"(.+?)\".+?<h2 class=\"title\">(.+?)<\/h2>.+?<\/section>/";
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        //$this->log($matches,LOG_DEBUG);
        $results = [];
        foreach ($matches as $match) {
            $match_img = preg_replace('/-[0-9]{1,}x[0-9]{1,}/', '', $match[2]);
            $results[] = [
                'scrape_url' => $match[1],
                'scrape_img' => $match_img,
                'scrape_title' => $match[3],
                'display_title' => $this->convert_title($match[3], 20)
            ];
        }
        $hits = count($results);
        $limit = 10;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits
            ]
        ];
    }

    public function rakutenrecipe($query, $page = 1)
    {
        $url = "http://recipe.rakuten.co.jp/search/" . urlencode($query) . "/" . $page . "/?s=4&v=0&t=2";
        $opts = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: OreOreAgent\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $html = file_get_contents($url, false, $context);
        $html = preg_replace('/(\n|\r|\t)/', '', $html);

        $pattern = "/<div data-ratunit=\"item\">.+?<a href=\"(.+?)\".+?<img src=\"(.+?)\".+?(<h3>|<div class=\"cateRankTtl\">)(.+?)(<\/h3>|<\/div>)/";
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $results = [];
        foreach ($matches as $match) {
            $match_img = preg_replace('/\?thum=[0-9]{1,}/', '', $match[2]);
            $results[] = [
                'scrape_url' => "http://recipe.rakuten.co.jp" . $match[1],
                'scrape_img' => $match_img,
                'scrape_title' => $match[4],
                'display_title' => $this->convert_title($match[4], 20)
            ];
        }
        $hits = count($results);
        $limit = 20;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ]
        ];
    }
    public function roomclip($query, $page = 1)
    {
        $url = "http://roomclip.jp/tag/search?keyword=" . urlencode($query) . "&page=" . $page;
        $html = file_get_contents($url);
        $html = preg_replace('/(\n|\r|\t)/', '', $html);
        $pattern = "/<div class=\"entry-tile\".+?href=\"(.+?)\".+?src=\"(.+?)\".+?title=\"(.+?)\".+?<\/a>/";
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $results = [];
        foreach ($matches as $match) {
            preg_match('/\/img_(.+?)\//', $match[2], $size);
            $match_img = preg_replace('/\/' . $size[1] . '\//', '/750/', $match[2]);
            $match_img = preg_replace('/\/img_' . $size[1] . '\//', '/img_750/', $match_img);
            $results[] = [
                'scrape_url' => "http://roomclip.jp" . $match[1],
                'scrape_img' => $match[2],
                'scrape_title' => $match[3],
                'display_title' => $this->convert_title($match[3], 20)
            ];
        }
        $hits = count($results);
        $limit = 24;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }
    public function beautybox($query, $page = 1)
    {
        $results = [];
        $BeautyBox_url = 'http://www.beauty-box.jp/?value=' . urlencode($query) . '&s=2&page=' . $page . '';
        $BeautyBox_html = file_get_contents($BeautyBox_url);
        preg_match('/<div class="sytle_list line05">.+?<\/div>/s', $BeautyBox_html, $match_div);
        preg_match_all('/<p.*?<\/p>/', $match_div[0], $match_p);
        foreach ($match_p[0] as $p) {
            preg_match('/<img.*?\/>/', $p, $match_img);
            preg_match('/http.+?(jpg|gif|jpeg)/', $match_img[0], $match_img);
            $match_img = preg_replace('/-[0-9]{1,}x[0-9]{1,}/', '', $match_img[0]);
            preg_match('/<a href=.*?\/">/', $p, $match_href);
            $match_href = preg_replace('/<a href="/', '', $match_href[0]);
            $match_href = preg_replace('/">/', '', $match_href);
            preg_match('/<span>.*?<\/span>/', $p, $match_span);
            $match_span = preg_replace('/<span>/', '', $match_span[0]);
            $match_span = preg_replace('/<\/span>/', '', $match_span);
            $comment = "";
            $display_title = $this->convert_title($match_span, 20);

            $results[] = [
                'scrape_img' => $match_img,
                'scrape_url' => $match_href,
                'scrape_title' => $match_span,
                'display_title' => $display_title,
                'comment' => $comment
            ];
        }
        $hits = count($results);
        $limit = 45;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ]
        ];
    }
    public function recipeblog($query, $page = 1)
    {
        $results = [];
        $search_multiple_page = 3;

        for ($i = 0; $i < $search_multiple_page; $i++) {
            $RecipeBlog_url = 'http://www.recipe-blog.jp/search/recipe?page=' . $page . '&keyword=' . urlencode($query) . '&sort_order=score';
            $RecipeBlog_html = file_get_contents($RecipeBlog_url);
            preg_match('/<div id="resultWrap">.+?<!--\/resultWrap -->/s', $RecipeBlog_html, $match_resultWrap);
            if (!in_array(0, $match_resultWrap)) {
                $match_resultWrap[0] = "";
            }
            preg_match_all('/<div class="resultImage">.+?<!-- \/resultImage -->/s', $match_resultWrap[0], $match_resultImage);
            foreach ($match_resultImage[0] as $match_div) {
                preg_match('/http.+?jpg/', $match_div, $match_jpg);
                $match_jpg = preg_replace('/[0-9]{1,}x[0-9]{1,}\.cut/', '400x0.none', $match_jpg[0]);
                preg_match('/alt=".+?"/', $match_div, $match_title);
                $match_title = preg_replace('/alt="/', '', $match_title);
                $match_title = preg_replace('/"/', '', $match_title);
                preg_match('/<a href=".+?">/', $match_div, $match_url);
                $match_url = preg_replace('/<a href="/', '', $match_url);
                $match_url = preg_replace('/">/', '', $match_url);
                $comment = "";
                if (isset($match_title[0])) {
                    $display_title = $this->convert_title($match_title[0], 20);
                    $results[] = [
                        'scrape_img' => $match_jpg,
                        'scrape_url' => $match_url[0],
                        'scrape_title' => $match_title[0],
                        'display_title' => $display_title,
                        'comment' => $comment
                    ];
                } else {
                    $results[] = [
                        'scrape_img' => "",
                        'scrape_url' => "",
                        'scrape_title' => "",
                        'display_title' => "",
                        'comment' => ""
                    ];
                }
            }
            $page++;
        }
        $page = $page - 1;
        $hits = count($results);
        $limit = 10 * $search_multiple_page;
        if ($hits == $limit) {
            $nextpage = true;
        } else {
            $nextpage = false;
        }

        return [
            'results' => $results,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'nextpage' => $nextpage,
                'hits' => $hits,
            ],
        ];
    }
    public function form_html($data, $sitename, $object)
    {
        $dom = "";
        if ($object == "img") {
            if ($sitename == "beautybox") {
                $img_width = 120;
                $img_height = 160;
            } else if ($sitename == "wear") {
                $img_width = 116;
                $img_height = 138;
            } else {
                $img_width = 120;
                $img_height = 120;
            }
            $li_height = $img_height + 30;
            $btn_top = ($img_height - 28) / 2;
            $btn_left = ($img_width - 70) / 2;

            foreach ($data['results'] as $result) {
                if ($sitename == "gurunavi") {
                    $scrape_add = "'" . h($result['scrape_img']) . "','" . h($result['scrape_url']) . "','" . h($result['scrape_title']) . "','" . h($result['comment']) . "','" . h($result['address']) . "','" . h($result['tel']) . "','" . h($result['budget']) . "'";
                    $dom = $dom . '<li class="' . $sitename . '-item" style="position: relative;float:left;display:inline;margin:3px;width:' . $img_width . 'px;height:' . $li_height . 'px;"><img class="scrape-img" style="margin:0;width:' . $img_width . 'px;height:' . $img_height . 'px;" src="' . $result['scrape_img'] . '"><div class="background" style="background-color:black;width:' . $img_width . 'px;height:' . $img_height . 'px;position:absolute;left:0;top:0;opacity:0"></div><a href="javascript:void(0)" style="position:absolute;left:' . $btn_left . 'px;top:' . $btn_top . 'px;opacity:0;text-decoration:none;color:white" onclick="scrape_add(' . $scrape_add . ');return false;" class="btn scrape-add-btn">追加する</a><div class="gurunavi-item-title" style="font-size:5px;color:black;height:28px;">' . $result['display_title'] . '</div></li>';
                } else if ($sitename == "instagram") {
                    $scrape_add = "'" . h($result['scrape_img']) . "','" . h($result['scrape_url']) . "','',''";
                    $dom = $dom . '<li class="' . $sitename . '-item" style="position: relative;float:left;display:inline;margin:3px;width:' . $img_width . 'px;height:' . $li_height . 'px;"><img class="scrape-img" style="margin:0;width:' . $img_width . 'px;height:' . $img_height . 'px;" src="' . $result['scrape_img'] . '"><div class="background" style="background-color:black;width:' . $img_width . 'px;height:' . $img_height . 'px;position:absolute;left:0;top:0;opacity:0"></div><a href="javascript:void(0)" style="position:absolute;left:' . $btn_left . 'px;top:' . $btn_top . 'px;opacity:0;text-decoration:none;color:white" onclick="scrape_add(' . $scrape_add . ');return false;" class="btn scrape-add-btn">追加する</a><div class="gurunavi-item-title" style="font-size:5px;color:black;height:28px;">' . $result['display_title'] . '</div></li>';
                } else if ($sitename == "wear") {
                    $scrape_add = "'" . h($result['scrape_img']) . "','" . h($result['scrape_url']) . "','',''";
                    $dom = $dom . '<li class="' . $sitename . '-item" style="position: relative;float:left;display:inline;margin:3px;width:' . $img_width . 'px;height:' . $li_height . 'px;"><img class="scrape-img" style="margin:0;width:' . $img_width . 'px;height:' . $img_height . 'px;" src="' . $result['scrape_img'] . '"><div class="background" style="background-color:black;width:' . $img_width . 'px;height:' . $img_height . 'px;position:absolute;left:0;top:0;opacity:0"></div><a href="javascript:void(0)" style="position:absolute;left:' . $btn_left . 'px;top:' . $btn_top . 'px;opacity:0;text-decoration:none;color:white" onclick="scrape_add(' . $scrape_add . ');return false;" class="btn scrape-add-btn">追加する</a><div class="gurunavi-item-title" style="font-size:5px;color:black;height:28px;"></div></li>';
                } else {
                    $scrape_add = "'" . h($result['scrape_img']) . "','" . h($result['scrape_url']) . "','" . h($result['scrape_title']) . "',''";
                    $dom = $dom . '<li class="' . $sitename . '-item" style="position: relative;float:left;display:inline;margin:3px;width:' . $img_width . 'px;height:' . $li_height . 'px;"><img class="scrape-img" style="margin:0;width:' . $img_width . 'px;height:' . $img_height . 'px;" src="' . $result['scrape_img'] . '"><div class="background" style="background-color:black;width:' . $img_width . 'px;height:' . $img_height . 'px;position:absolute;left:0;top:0;opacity:0"></div><a href="javascript:void(0)" style="position:absolute;left:' . $btn_left . 'px;top:' . $btn_top . 'px;opacity:0;text-decoration:none;color:white" onclick="scrape_add(' . $scrape_add . ');return false;" class="btn scrape-add-btn">追加する</a><div class="gurunavi-item-title" style="font-size:5px;color:black;height:28px;">' . $result['display_title'] . '</div></li>';
                }
            }
            return [
                'results' => $data['results'],
                'meta' => $data['meta'],
                'dom' => $dom,
            ];
        }
        if ($object == "spot") {
            foreach ($data['results'] as $result) {
                $scrape_add = "'" . h($result['scrape_img']) . "','" . h($result['scrape_url']) . "','" . h($result['scrape_title']) . "','" . h($result['comment']) . "','" . h($result['address']) . "','" . h($result['tel']) . "','" . h($result['budget']) . "'";
                $dom = $dom . '<li class="gurunavi-item" style="width:700px;height:150px;margin:10px 0;padding:10px;border:1px solid #ddd;background-color:white"><div class="spot-img" style="float:left;width:150px"><img src="' . $result['scrape_img'] . '" alt="' . h($result['scrape_title']) . '" style="margin:0;width:130px;height:130px;"></div><div class="spot-detail" style="float:left;width:400px"><p class="spot-title" style="font-size:16px;font-weight:700;">' . h($result['scrape_title']) . '</p><p class="spot-address" style="color:#999;">' . $result['address'] . '</p><p class="spot-access" style="margin-top:6px;">' . $result['access'] . '</p><p class="spot-budget" style="color:#999;">' . $result['budget'] . '</p></div><div class="spot-button" style="float:right;width:70px;margin:50px 10px 0 0;"><a href="javascript:void(0)" onclick="scrape_add(' . $scrape_add . ');return false;" class="btn btn-danger">追加する</a></div></li>';
            }
            return [
                'results' => $data['results'],
                'meta' => $data['meta'],
                'dom' => $dom,
            ];
        }
        if ($object == "product") {
            return [
                'results' => $data['results'][0]["amazon"]['LargeImage']['URL'],
                'meta' => $data['meta'],
                'dom' => $dom,
            ];
        }
    }
    public function convert_title($title, $max)
    {
        $title_length = mb_strlen($title);
        if ($title_length > $max) {
            $display_title = mb_substr($title, 0, $max) . "…";
        } else {
            $display_title = $title;
        }
        return $display_title;
    }
    public function check_api_data($data, $datatype)
    {
        if (gettype($data) == $datatype) {
            $return = $data;
        } else {
            $return = "";
        }
        return $return;
    }
    public function check_api_item($data, $datatype, $index)
    {
        if (gettype($data) == $datatype) {
            if ($data == "") {
                $return = "";
            } else {
                $return = '・' . $index . '：' . $data . '\n';
            }
        } else {
            $return = "";
        }
        return $return;
    }
}
