<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 05.08.14
 * Time: 15:48
 */
Class GetSitemap extends BaseCrawler
{
    private $letters = array('а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф',
        'х', 'ц', 'ш', 'щ', 'э', 'ю', 'я', 'en', 'num');
    protected $xpath;
    protected $project = 'thelib';

    public function __construct($db)
    {
        parent::__construct($db);
    }

    public function DetectBooks()
    {

//        foreach($this->letters AS $letter)
//        {
//            $url = 'http://thelib.ru/abc/books?letter=' . urlencode($letter);
//            $this->AddtoCrawl($url, 0, 'list');
//
//        }
//        die();

        while (true) {
            //$sql = 'SELECT * FROM '  . $this->project . '_urls WHERE type = "book" AND status = 0 LIMIT 100';
            $sql = 'SELECT * FROM ' . $this->project . '_urls WHERE httpcode in ("","502", "503")  LIMIT 100';
            $rows = $this->_conn->select($sql, 'arows');
            if (empty($rows)) break;
            foreach ($rows AS $row) {
                $url = $row['url'];

                $html = $this->get_html($url);

                if ($row['type'] == 'list' && $this->last_http_code == '200') {
                    $dom = new DomDocument;
                    $dom->loadHTML($html);

                    $this->xpath = new DomXPath($dom);
                    $next = $this->XpathNodeValue('//*[contains(@class, "next")]/a', 'attrValue', 'href');

                    if (!empty($next)) {
                        $this->AddtoCrawl($next, $row['id'], 'list');
                    }

                    $elements = $this->XpathNodeValue('//*[contains(@class, "column")]/li/a', 'attrValues', 'href');

                    if (!empty($elements))
                        foreach ($elements AS $url) {
                            if ($url) {
                                $this->AddtoCrawl($url, $row['id'], 'book');
                            }
                        }
                }

                $this->ChangeUrlStatus($row['id'], $this->last_http_code, 1);
                if ($this->last_http_code == '200')
                    $this->save_html($row['id'], $html);

            }
        }
    }
}