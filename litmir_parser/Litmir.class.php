<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:03
 */
include_once '../library/BaseParser.php';
Class LitmirBP extends BaseParser
{
    protected $xpath = '';
    protected $book = array();
    protected $conn;
    protected $config = array(
        'name' => '//*[contains(@class, "lt35")]',
        'lt36' => '//*[contains(@class, "lt36")]/a',
        'rating' => '//*[contains(@jq, "book_rating")]',
        'ISBN' => '',
        'pub_house' => '//*[contains(@itemprop, "publisher")]/a',
        'img_url' => '//*[contains(@class, "lt34")]/img',
        'download_links' => '//*[contains(@jq, "BookFile")]/td[2]/a',
        'download_formats' => '//*[contains(@jq, "BookFile")]/td[3]',
        'download_sizes' => '//*[contains(@jq, "BookFile")]/td[4]',
        'download_dates' => '//*[contains(@jq, "BookFile")]/td[6]',
        'reviews_descr' => '//*[contains(@class, "BBHtmlCodeInner")]', //array
        'date_published' => '//*[contains(@class, "cm28")]',

        'series' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[1]/a',
        'links' => '//*[contains(@class, "sources actionbar bar-vertical")]/table/tr/td/a',
        'description' => '//*[contains(@jq, "BookAnnotationText")]',
        'info' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[4]',
        'similar_books' => '//*[contains(@class, "bookinfo")]/div/a',
        //'reviews_descr' => '//*[contains(@itemprop, "reviewBody")]',//array
        //'date_published' => '//*[contains(@itemprop, "datePublished")]',//array [getAttribute("content")]
        'comment_quality' => '//*[contains(@class, "vote action action-text")]', //array
        'review_rating' => '//*[contains(@title, "рейтинг ")]' //title [getAttribute("title")]

    );
    private $preg_match = array(
        'ISBN' => '~<b>ISBN:</b> (.*?) <br />~is',
        'page_num' => '~<b>Количество страниц:</b> (.*?) </div><div class="lt36">~is',
        'pub_house' => '~<b>Издатель:</b> (.*?) <br />~is',
        'comment_rating' => '~книгу на (\d*?)</i>~is'
    );

    public function __construct($conn)
    {
        $cache_path = CACHE_PATH . 'litmir/';
        parent::__construct('litmir', $cache_path, $conn);
    }


    public function GetBookInfo($book_id)
    {
        $this->book = array();
        $html = $this->GetHtml($book_id);

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
        $this->book['lt36'] = $this->XpathNodeValue($this->config['lt36'], 'key_value', 'href');
        $this->book['info'] = '';
        $this->book['author'] = !empty($this->book['lt36'][0][0]) ? mysql_real_escape_string($this->book['lt36'][0][0]) : '';
        $this->book['author_url'] = !empty($this->book['lt36'][0][1]) ? $this->book['lt36'][0][1] : '';
        $this->book['genres'] = array(!empty($this->book['lt36'][1][0]) ? $this->book['lt36'][1][0] : '');
        $this->book['rating'] = $this->XpathNodeValue($this->config['rating']);
        $this->book['isbn'] = $this->ClearIsbn($this->GetPregRes($html, $this->preg_match['ISBN']));
        $this->book['page_num'] = $this->GetPregRes($html, $this->preg_match['page_num']);
        $this->book['pub_house'] = explode(',', $this->GetPregRes($html, $this->preg_match['pub_house']));
        $this->book['description'] = mysql_real_escape_string($this->XpathNodeValue($this->config['description']));
        $this->book['img_url'] = $this->XpathNodeValue($this->config['img_url'], 'attrValues', 'src');
        $this->book['download_links'] = $this->XpathNodeValue($this->config['download_links'], 'attrValues', 'href');
        $this->book['download_formats'] = $this->XpathNodeValue($this->config['download_formats'], 'nodeValues');
        $this->book['download_sizes'] = $this->XpathNodeValue($this->config['download_sizes'], 'nodeValues');
        $this->book['download_dates'] = $this->XpathNodeValue($this->config['download_dates'], 'nodeValues');
        $this->book['reviews_descr'] = $this->XpathNodeValue($this->config['reviews_descr'], 'nodeValues');
        $this->book['date_published'] = $this->XpathNodeValue($this->config['date_published'], 'nodeValues');
        $this->book['comment_rating'] = $this->GetPregRes($html, $this->preg_match['comment_rating'], 'all');

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