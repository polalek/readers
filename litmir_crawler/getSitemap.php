<?php
/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 05.08.14
 * Time: 15:48
 */
Class GetSitemap extends BaseCrawler
{
    private $last_page = 191198;
    protected $project = 'litmir';


    public function __construct($database)
    {
        parent::__construct($database);
    }

    public function DetectBooks()
    {
        for ($i = 0; $i <= $this->last_page; $i++) {
            $url = 'http://www.litmir.net/bs?order=rating_avg_down&p=' . $i;
            $dom = new DomDocument;
            $html = $this->get_html($url);

            $dom->loadHTML($html);

            $xpath = new DomXPath($dom);
            $elements = $xpath->query('//*[contains(@class, "lt24")]');
            //var_dump($elements); die();
            foreach ($elements AS $item) {
                $url = $item->getAttribute("href");
                if ($url) {
                    $this->AddtoCrawl($url, 0, 'book');
                }
            }
        }
    }

    public function GetBookCache()
    {
        while (true) {
            $sql = 'SELECT * FROM ' . $this->project . '_urls WHERE type = "book" AND (status = 0 OR httpcode="") LIMIT 100';
            $books = $this->_conn->select($sql, 'arows');

            if (empty($books))
            {
                sleep(2); continue;
            }

            foreach ($books AS $book) {
                $url = 'http://www.litmir.net/' . $book['url'];
                $html = $this->get_html($url);

                $this->ChangeUrlStatus($book['id'], $this->last_http_code, 1);

                if ($this->last_http_code == '200')
                    $this->save_html($book['id'], $html);

            }

        }

    }

}