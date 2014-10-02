<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 05.08.14
 * Time: 15:48
 */
Class GetSitemap extends BaseCrawler
{
    protected $xpath;
    protected $project = 'ereading';

    public function __construct($db)
    {
        parent::__construct($db);
    }

    public function DetectBooks()
    {
        /*for($i=1;$i<53;$i++)
        {
            $url = '/bookbytypes.php?type=' . $i;
            $this->AddtoCrawl($url, 0, 'genre');
        }

        die(); */
        $cur_id = 0;
        $step = 100;

        while (true) {

            //$sql = 'SELECT * FROM ereading_urls WHERE status = 0 LIMIT 100';
            $sql = 'SELECT * FROM ereading_urls WHERE id>'.$cur_id . ' AND id<' . ($cur_id+$step);

            $rows = $this->_conn->select($sql, 'arows');
            if (empty($rows)) break;
            $cur_id += $step;
            foreach ($rows AS $row) {
                $url = 'http://www.e-reading.me' . $row['url'];
                $cookie = '__qca=P0-1819578994-1409088183756; ASPXSID=h2h237t0ggdtdndj64hsnu28m1; pagelang=ru; fmathcap=10; screenwidth=1280; rgoods_1=5; __utma=222089100.905714611.1409088162.1409088162.1409241974.2; __utmb=222089100.16.10.1409241974; __utmc=222089100; __utmz=222089100.1409088162.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); MarketGidStorage=%7B%220%22%3A%7B%22svspr%22%3A%22http%3A%2F%2Fwww.e-reading.me%2Fbookbytypes.php%3Ftype%3D52%22%2C%22svsds%22%3A5%2C%22TejndEEDj%22%3A%22MTQwOTI0MTk4NzY2NjQ1MzA1NjIz%22%7D%2C%22C45305%22%3A%7B%22page%22%3A2%2C%22time%22%3A1409243684294%7D%7D';
                $dom = new DomDocument;
                $html = $this->get_html($url, $cookie);

                if ($row['type'] == 'genre' && $this->last_http_code == '200') {

                    $dom->loadHTML($html);

                    $xpath = new DomXPath($dom);
                    $paginators = $xpath->query('//*[contains(@href, "bookbytypes.php?")]');

                    if (!empty($paginators->item(0)->nodeValue)) {
                        foreach ($paginators AS $paginator) {

                            $this->AddtoCrawl('/' . str_replace('http://www.e-reading.mobi/', '', $paginator->getAttribute('href')), $row['id'], 'genre');
                        }
                    }

                    $elements = $xpath->query("//*[contains(@href, 'book.php?book=')]");

                    foreach ($elements AS $item) {

                        $this->AddtoCrawl($item->getAttribute("href"), $row['id'], 'book');

                    }
                }

                $this->ChangeUrlStatus($row['id'], $this->last_http_code, 1);

                if ($this->last_http_code == '200')
                    $this->save_html($row['id'], $html);

            }
        }
    }
}