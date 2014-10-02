<?php
/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 29.06.14
 * Time: 1:01
 */
Class GenerParser extends BaseCrawler
{
    private $genres;
    protected $project = 'livelib';

    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function DetectBooks($num = 1)
    {
        $cur_id = 0;
        $step = 100;
        while (true) {

            //$sql = 'SELECT * FROM livelib_urls WHERE httpcode in ("","502", "503", "429") AND queue=' . $num . ' LIMIT 100';
            $sql = 'SELECT * FROM livelib_urls ORDER BY status ASC, date ASC LIMIT 100';
            $rows = $this->_conn->select($sql, 'arows');
            if (empty($rows)) break;

            $cur_id += $step;
            foreach ($rows AS $row) {
               // echo $row['queue'] . '  : ' . $num . "\n";
               // if($row['queue'] != $num) continue;

                $cookie = '__utma=195693994.462616126.1403784410.1410179442.1410184305.24; __utmz=195693994.1409299185.16.4.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=(not%20provided); LiveLibId=khfek5pk104886hp35tcdtige1; iwatchyou=122d2803083ce7f465165bcfa7010362; _gat=1; innerpage=1; _ga=GA1.2.462616126.1403784410; _ym_visorc_127861=w';
                $url = 'http://livelib.ru' . $row['url'];

                $dom = new DomDocument;
                $html = $this->get_html($url, $cookie);

                $dom->loadHTML($html);

                $xpath = new DomXPath($dom);
                if ($this->last_http_code == '200') {
                    if (in_array($row['type'], array('tag', 'genre', 'genres'))) {

                        $next = $xpath->query('//*[contains(@id, "a-list-page-next")]');

                        if (!empty($next->item(0)->nodeValue)) {
                            $this->AddtoCrawl($next->item(0)->getAttribute('href'), $row['id'], 'genre');
                        }

                        $elements = $xpath->query("//*[contains(@class, 'bookinfo')]/div/a");

                        foreach ($elements AS $item) {
                            $url = $item->getAttribute("href");
                            if ($url) {
                                $this->AddtoCrawl($url, $row['id'], 'book');
                            }
                        }
                    } elseif ($row['type'] == 'reviews') {
                        $paginators = $xpath->query('//*[contains(@id, "a-list-page-")]');

                        if (!empty($paginators->item(0)->nodeValue)) {

                            foreach ($paginators AS $paginator) {
                                $purl = $paginator->getAttribute('href');
                                if (!empty($purl))
                                    $this->AddtoCrawl($purl, $row['id'], 'reviews');
                            }
                        }
                    } elseif ($row['type'] == 'book') {
                        $add_url = $row['url'] . '/reviews';
                        $add_url_id = $this->GetUrlId($add_url);
                        if (empty($add_url_id))
                            $this->AddtoCrawl($add_url, $row['id'], 'reviews');

                        $work = $xpath->query('//*[@class ="work"]/a');
                        if (!empty($work->item(0)->nodeValue)) {
                            $this->AddtoCrawl($work->item(0)->getAttribute('href'), $row['id'], 'work');
                        }

                    }
                }

                $this->ChangeUrlStatus($row['id'], $this->last_http_code, 1);

                if ($this->last_http_code == '200' && strpos($html, 'уйста, введите текст с картинки.') === FALSE)
                    $this->save_html($row['id'], $html);

            }
        }
    }

//    public function getBookReviews()
//    {
//        while (true) {
//            $sql = 'SELECT * FROM livelib_urls WHERE (type="book" AND status=1) OR (type = "reviews" and status=0) LIMIT 100';
//            $rows = $this->_conn->select($sql, 'arows');
//            if (empty($rows)) break;
//
//            foreach ($rows AS $row) {
//                $url = $row['url'] . '/reviews';
//                if (strpos($url, 'http') === FALSE)
//                    $url = 'http://www.livelib.ru' . $url;
//
//                $url_id = $this->GetUrlId($url);
//                if (empty($url_id))
//                    $url_id = $this->AddtoCrawl($url, $row['id'], 'reviews');
//
//                $cookie = 'LiveLibId=aj5dvp1tigj8hbn97hc4v25ve5; __utma=195693994.549449873.1409000597.1409087875.1409250461.4; __utmb=195693994.16.10.1409250461; __utmc=195693994; __utmz=195693994.1409000597.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); iwatchyou=69cff91a2c68f6e59f8ce22b36cf34a5';
//                //$html = file_get_contents($url);
//                $html = $this->get_html($url, $cookie);
//
//                if ($this->last_http_code == '200') {
//
//                    $dom = new DomDocument;
//                    $dom->loadHTML($html);
//
//                    $xpath = new DomXPath($dom);
//                    $paginators = $xpath->query('//*[contains(@id, "a-list-page-")]');
//
//                    if (!empty($paginators->item(0)->nodeValue)) {
//
//                        foreach ($paginators AS $paginator) {
//                            $purl = $paginator->getAttribute('href');
//                            if (!empty($purl))
//                                $this->AddtoCrawl('http://www.livelib.ru/' . $purl, $row['id'], 'reviews');
//                        }
//                    }
//                }
//                $this->ChangeUrlStatus($url_id, $this->last_http_code, 1);
//                $this->ChangeUrlStatus($row['id'], 0, 5);
//
//                if ($this->last_http_code != '200')
//                    $this->save_html($url_id, $html);
//
//            }
//
//        }
//
//    }

}