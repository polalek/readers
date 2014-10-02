<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:03
 */
include_once '../library/BaseParser.php';
Class EreadingParser extends BaseParser
{
    protected $xpath = '';
    protected $book = array();
    protected $conn;
    protected $config = array(
        'name' => '//*[@itemprop="name"]',
        'author' => '//*[contains(@href, "bookbyauthor.php")]',
        //'author_url' => '->getAttribute("href")',
        //'pub_house' => '//*[contains(@itemprop, "publisher")]/a',
        'genres' => '//*[@itemprop="category genre"]',
        'img_url' => '//*[@itemprop="image"]',
        //'series' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[1]/a',
        //'links' => '//*[contains(@class, "sources actionbar bar-vertical")]/table/tr/td/a',
        'description' => '//*[@itemprop= "description"]',
        //'info' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[4]',
        //'similar_books' => '//*[contains(@class, "bookinfo")]/div/a',
        'reviews_descr' => '//*[@property= "v:description"]', //array
        'date_published' => '//*[@property= "v:dtreviewed"]', //array [getAttribute("content")]
        //'comment_quality' => '//*[contains(@class, "vote action action-text")]', //array
        'reviews_author' => '//*[@property= "v:reviewer"]',
        'comment_rating' => '//*[@property= "v:rating"]', //title [getAttribute("title")]
        'download_links' => '//*[contains(@href, "lrf.php") or contains(@href, "download.php") or contains(@href, "epub.php") or contains(@href, "mobi.php") or contains(@href, "txt.php")]'
         // 'download_links' => '//*[contains(@href, "lrf.php") or contains(@href, "download.php")]'
        //'isbn' => '//*[contains(@itemprop, "isbn")]'


    );
    private $preg_match = array(
        'author' => '~Автор:(.*?)\<\/strong\>~is',
        'serie' => '~<b>Серия:(.*?)<\/a>~is',
        'pub_house' => '~Издание:(.*?)" \/>~is',
        'download_size' => '~эту книгу \((.*?)\) в~ius',
        //'pub_house' => '~Оценка (\d*?) из~is'
    );

    public function __construct($conn)
    {
        $cache_path = CACHE_PATH . 'ereading/';
        parent::__construct('ereading', $cache_path, $conn);
    }


    public function GetBookInfo($book_id)
    {
        $this->book = array();


        $html = $this->GetHtml($book_id);

        //$html =  mb_convert_encoding( $html, 'Windows-1251', 'UTF-8');

        /* $tidy = new tidy();
        $tidy_config = array();
        $html= $tidy->repairString($html,$tidy_config, 'utf8');*/
        $dom = new DomDocument;
        //$html = str_ireplace('id="top-anchor"', '', $html);
        $dom->loadHTML($html);
        //echo $html;
        // var_dump($dom);
        $this->xpath = new DomXPath($dom);
        //var_dump($this->xpath);
        $this->book['name'] = mysql_real_escape_string($this->XpathNodeValue($this->config['name']));
        $this->book['clear_name'] = $this->clearString($this->book['name']);
        $this->book['url_id'] = $book_id;
        $this->book['author'] = strip_tags($this->XpathNodeValue($this->config['author']));
       // $this->book['author_url'] = $this->XpathNodeValue($this->config['author'], 'attrValue', 'href');


       // $this->book['pub_urls'] = $this->XpathNodeValue($this->config['pub_house'], 'attrValues', 'href');

        $this->book['genres'] = $this->XpathNodeValue($this->config['genres'], 'nodeValues');
        //$this->book['genres_urls'] = $this->XpathNodeValue($this->config['genres'], 'attrValues', 'href');

        //$this->book['isbn'] = $this->ClearIsbn($this->XpathNodeValue($this->config['isbn']));
        //$this->book['pub_houses'] = $this->SavePubHouses();



        $this->book['img_url'] = $this->XpathNodeValue($this->config['img_url'], 'attrValues', 'src');

        $this->book['description'] = mysql_real_escape_string($this->XpathNodeValue($this->config['description']));

        $this->book['info'] = '';

        $this->book['reviews_descr'] = $this->XpathNodeValue($this->config['reviews_descr'], 'nodeValues');
        $this->book['date_published'] = $this->XpathNodeValue($this->config['date_published'], 'nodeValues');
        //$this->book['comment_quality'] = $this->XpathNodeValue($this->config['comment_quality'], 'nodeValues');
        $this->book['comment_rating'] = $this->XpathNodeValue($this->config['comment_rating'], 'nodeValues');

        $this->book['download_links'] = $this->XpathNodeValue($this->config['download_links'], 'attrValues', 'href');
        $this->book['download_formats'] = $this->XpathNodeValue($this->config['download_links'], 'nodeValues');

        $html = iconv('windows-1251', 'utf-8', $html);
        $this->book['download_size'] = trim(strip_tags($this->GetPregRes($html, $this->preg_match['download_size'])));
        $this->book['pub_house'] = trim(strip_tags($this->GetPregRes($html, $this->preg_match['pub_house'])));
        $this->book['serie'] = trim(strip_tags($this->GetPregRes($html, $this->preg_match['serie'])));

        if(!empty($this->book['download_links'])){
            for($i=0; $i<count($this->book['download_links']);$i++)
                $this->book['download_sizes'][$i] = $this->book['download_size'];
        }

        return $this->book;
    }


    public function SaveBook()
    {
        if (!is_array($this->book) || empty($this->book['clear_name'])) return false;

        $this->SaveAuthor();
        $this->SaveSerie();
        $this->SaveB();
        $this->SavePubHouses();

        if (!empty($this->book['id'])) {

            $this->SaveReviews();
            $this->SaveDownloadLinks();

            $this->SaveGenres();
            $this->SaveImageUrls();
        }
        return true;
        //$this->AddBooks2Crawl(json_decode($this->book['similar_books']));

    }


}