<?php
/**
 * Created by JetBrains PhpStorm.
 * User: apolunin
 * Date: 25.09.14
 * Time: 12:13
 * To change this template use File | Settings | File Templates.
 */

include_once 'config.php';
include './library/BaseCrawler.php';
include_once './library/db.class.php';

$db = new db();

function get_page($url, $cookie = '')
{
    $cookie='pagelang=ru; screenwidth=1920; __utma=222089100.1327932590.1411641848.1411641848.1412228575.2; __utmz=222089100.1411641848.1.1.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=(not%20provided); ASPXSID=b1t3tbk1bjmp694euktjsii420; __utmb=222089100.1.10.1412228575; __utmc=222089100; __utmt=1';
    echo 'url: ' . $url . PHP_EOL;
    $ports = array('9050', '9051', '9052', '9053', '9054', '9055', '9056', '9057', '9058', '9059');

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1, // return web page
        //CURLOPT_PROXY => 'socks5://127.0.0.1:' . $ports[rand(0, 9)],
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.143 Safari/537.36", // who am i
        CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
        CURLOPT_TIMEOUT => 120, // timeout on response
       CURLOPT_COOKIE => $cookie,
        CURLOPT_FOLLOWLOCATION => 1,
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);
    $err = curl_error($ch);

    if (!empty($err))
        var_dump($err);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    echo 'HTTP code:' . $http_code . '; content_length:' . $content_length . ' DONE get_html' . PHP_EOL;

    return array('html' => $html, 'http_code' => $http_code, 'content_length' => $content_length, 'content_type' => $content_type);
}


function save($url_id, $html, $type = 'images', $extension = '')
{
    if (empty($html)) return;

    $fc = substr($url_id, strlen($url_id) - 1, 1);
    $sc = strlen($url_id) > 1 ? substr($url_id, strlen($url_id) - 2, 1) : 0;
    $root_path = CACHE_PATH . $type . '/' . $sc . '/';

    if (!is_dir($root_path))
        mkdir($root_path);

    $root_path .= $fc . '/';
    if (!is_dir($root_path))
        mkdir($root_path);

    echo 'saving html to: ' . $root_path . '/' . $url_id . '.' . $extension . PHP_EOL;

    return file_put_contents($root_path . '/' . $url_id . '.' . $extension, $html);
}

$type = !empty($argv[1]) && in_array($argv[1], array('images', 'files')) ? $argv[1] : 0;

if (empty($type)) die('incorrect type');

while (true) {
    if ($type == 'images') {
        $sql = 'SELECT * FROM image_urls WHERE status = 0 LIMIT 200';
        $urls = $db->select($sql, 'carows', 'id');

        foreach ($urls AS $id => $u) {
            if ($u['from'] == 'ereading')
                $url = 'http://www.e-reading.me' . $u['url'];
            else
                $url = $u['url'];

            $response = get_page($url);
            if (!empty($response['html'])) {
               if($response['http_code']=='200'){
                $extension = str_replace('image/', '', $response['content_type']);
                save($id, $response['html'], 'images', $extension);
               }
                $sql = 'UPDATE image_urls SET status=1, httpcode="' . $response['http_code'] . '",
                content_length="' . $response['content_length'] . '",
                content_type="' . $response['content_type'] . '" WHERE id=' . $id;
                $db->exec($sql);


            }
        }

    } elseif ($type == 'files') {

        $sql = 'SELECT * FROM download_links WHERE status = 0 LIMIT 200';

        $urls = $db->select($sql, 'carows', 'id');

        foreach ($urls AS $id => $u) {
            if ($u['from'] == 'ereading')
                $url = 'http://www.e-reading.me/' . $u['url'];
            else
                $url = $u['url'];

            $response = get_page($url);
            var_dump($response); die();

            if (!empty($response['html'])) {

                if(strpos($response['content_type'], 'zip' )!== FALSE)
                {
                    $extension = 'zip';
                }
                else
                    $extension = $u['format'];

               if($response['content_length'] < 50000000)
                save($id, $response['html'], 'files', $extension);

                $sql = 'UPDATE download_links SET status=1, httpcode="' . $response['http_code'] . '",
                content_length="' . $response['content_length'] . '",
                content_type="' . $response['content_type'] . '" WHERE id=' . $id;
                $db->exec($sql);
            }
        }
    }
}

