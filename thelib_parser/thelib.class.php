<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:03
 */
include_once '../library/BaseParser.php';
Class ThelibBp extends BaseParser
{
    protected $xpath = '';
    protected $book = array();
    protected $conn;
    protected $config = array(
        'name' => '//*[@id="content"]/h1/text()',
        'author' => '//*[@id="authors"]/a',
        'series' => '//*[@id="series"]/div/a',
        'genres' => '//*[@id="genres"]/div/a',
        'img_url' => '//*[@id="cover"]/img',
        'description' => '//*[contains(@class, "annotation")]',
        'download_links' => '//*[@id="download"]/ul/li/a',
        'download_sizes' => '//*[@id="download"]/ul/li/text()',

        'reviews_descr' => '//*[contains(@class, "content")]', //array
        'date_published' => '//*[contains(@class, "time")]',
        //'comment_quality' => '//*[contains(@class, "vote action action-text")]', //array
        'review_rating' => '//*[contains(@title, "рейтинг ")]', //title [getAttribute("title")]
        'reviews_author' => '//*[@class="author"]', //title [getAttribute("title")]
    );

    private $preg_match = array();

    public function __construct($conn)
    {
        $cache_path = CACHE_PATH . "thelib/";
        parent::__construct('thelib', $cache_path, $conn);
    }


    public function GetBookInfo($url_id)
    {
        $this->book = array();
        $html = $this->GetHtml($url_id);

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
        $book_name = $this->XpathNodeValue($this->config['name']);
        $book_name = explode('-', $book_name);
        $this->book['name'] = mysql_real_escape_string(trim($book_name[1]));
        $this->book['clear_name'] = $this->clearString($this->book['name']);
        $this->book['url_id'] = $url_id;
        $this->book['author'] = mysql_real_escape_string(trim($book_name[0]));
        $this->book['genres'] = $this->XpathNodeValue($this->config['genres'], 'nodeValues');
        $this->book['serie'] = $this->XpathNodeValue($this->config['series']);
        $this->book['rating'] = '';
        $this->book['isbn'] = '';
        $this->book['info'] = '';
        $this->book['page_num'] = 0;
        $this->book['pub_house'] = '';
        $this->book['description'] = mysql_real_escape_string($this->XpathNodeValue($this->config['description']));
        $this->book['img_url'] = $this->XpathNodeValue($this->config['img_url'], 'attrValues', 'src');

        $this->book['download_links'] = $this->XpathNodeValue($this->config['download_links'], 'attrValues', 'href');
        $download_formats = $this->XpathNodeValue($this->config['download_links'], 'nodeValues');
        if (!empty($download_formats)) {
            foreach ($download_formats AS $download_format) {
                $download_format = str_replace('Скачать в формате ', '', $download_format);
                $this->book['download_formats'][] = trim(strtolower($download_format));
            }
        }

        $this->book['download_sizes'] = $this->XpathNodeValue($this->config['download_sizes'], 'nodeValues');
        if (!empty($this->book['download_sizes']))
            foreach ($this->book['download_sizes'] AS &$download_size) {
                $download_size = str_replace(' - ', '', $download_size);
            }

        $this->book['reviews_descr'] = $this->XpathNodeValue($this->config['reviews_descr'], 'nodeValues');
        $this->book['date_published'] = $this->XpathNodeValue($this->config['date_published'], 'nodeValues');
        $this->book['reviews_author'] = $this->XpathNodeValue($this->config['reviews_author'], 'nodeValues');
        // $this->book['comment_rating'] = $this->GetPregRes($html, $this->preg_match['comment_rating'], 'all');

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

        //$this->AddBooks2Crawl(json_decode($this->book['similar_books']));
        return true;
    }


}