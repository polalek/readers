<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:03
 */
include_once '../library/BaseParser.php';

Class LivelibBP extends BaseParser
{
    protected $xpath = '';
    protected $conn;
    protected $config = array(
        'name' => '//*[@id="leftside"]/div[2]/div[1]/h1',
        'author' => '//*[@id="leftside"]/div[2]/div[1]/h2/a',
        'author_url' => '->getAttribute("href")',
        'pub_house' => '//*[contains(@itemprop, "publisher")]/a',
        'genres' => '//*[contains(@class, "tag")]/a',
        'img_url' => '//*[@id="leftside"]/div[2]/div[1]/div[1]/a/img',
        'series' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[1]/a',
        'links' => '//*[contains(@class, "sources actionbar bar-vertical")]/table/tr/td/a',
        'description' => '//*[contains(@itemprop, "about")]',
        'info' => '//*[@id="leftside"]/div[2]/div[1]/div[2]/p[4]',
        'similar_books' => '//*[contains(@class, "bookinfo")]/div/a',
        'reviews_descr' => '//*[contains(@itemprop, "reviewBody")]', //array
        'date_published' => '//*[contains(@itemprop, "datePublished")]', //array [getAttribute("content")]
        'comment_quality' => '//*[contains(@class, "vote action action-text")]', //array
        'comment_rating' => '//*[contains(@itemprop, "ratingValue")]', //title [getAttribute("title")]
        'isbn' => '//*[contains(@itemprop, "isbn")]'

    );

    protected $preg_match = array(
        'comment_rating' => '~рейтинг (\d*?) из~is',
        'serie' => '~Серия: (.*?)<\/p>~is'
    );

    public function __construct($conn)
    {
        $this->conn = $conn;
        $cache_path = CACHE_PATH . "livelib/";
        parent::__construct('livelib', $cache_path, $conn);
    }
    public function GetBookInfo($book_id)
    {
        $this->book = array();
        $html = $this->GetHtml($book_id);
//        $tidy = new tidy();
//        $tidy_config = array();
//        $html = $tidy->repairString($html, $tidy_config, 'utf8');
        $dom = new DomDocument;
        $dom->loadHTML($html);
        $this->xpath = new DomXPath($dom);

        $this->book['name'] = $this->conn->real_escape_string($this->XpathNodeValue($this->config['name']));
        $this->book['clear_name'] = $this->clearString($this->book['name']);
        $this->book['url_id'] = $book_id;
        $this->book['author'] = $this->XpathNodeValue($this->config['author']);
        $this->book['author_url'] = $this->XpathNodeValue($this->config['author'], 'attrValue', 'href');

        $this->book['pub_house'] = $this->XpathNodeValue($this->config['pub_house'], 'nodeValues');
        $this->book['pub_urls'] = $this->XpathNodeValue($this->config['pub_house'], 'attrValues', 'href');

        $this->book['genres'] = $this->XpathNodeValue($this->config['genres'], 'nodeValues');

        if($this->book['genres'])
            $this->book['genres'] = array_splice($this->book['genres'], 0, count($this->book['genres'])-1);

        $this->book['genres_urls'] = $this->XpathNodeValue($this->config['genres'], 'attrValues', 'href');

        if($this->book['genres_urls'])
            $this->book['genres_urls'] = array_splice($this->book['genres_urls'], 0, count($this->book['genres_urls'])-1);

        $this->book['isbn'] = $this->ClearIsbn($this->XpathNodeValue($this->config['isbn']));
        //$this->book['pub_houses'] = $this->SavePubHouses();

        $this->book['serie'] = strip_tags($this->GetPregRes($html, $this->preg_match['serie']));

        if(strpos($this->book['serie'], '</a>'))
            $this->book['serie'] = $this->GetPregRes( $this->book['serie'], '~>(.*?)<\/a>~is');

        $this->book['shop_links'] = $this->XpathNodeValue($this->config['links'], 'attrValues', 'href');
        $this->book['img_url'] = $this->XpathNodeValue($this->config['img_url'], 'attrValues', 'src');
        $this->book['description'] = $this->conn->real_escape_string($this->XpathNodeValue($this->config['description']));
        $this->book['info'] = $this->conn->real_escape_string($this->XpathNodeValue($this->config['info']));
        $this->book['similar_books'] = json_encode($this->XpathNodeValue($this->config['similar_books'], 'attrValues', 'href'));
        $this->book['reviews_descr'] = $this->XpathNodeValue($this->config['reviews_descr'], 'nodeValues');
        $this->book['date_published'] = $this->XpathNodeValue($this->config['date_published'], 'attrValues', 'content');
        $this->book['comment_quality'] = $this->XpathNodeValue($this->config['comment_quality'], 'nodeValues');
        $this->book['comment_rating'] = array_splice($this->XpathNodeValue($this->config['comment_rating'], 'attrValues', 'content'), 1);

        // var_dump($this->book['comment_rating']); die();
        //$this->book['review_rating'] = $this->XpathNodeValue($this->config['review_rating'], 'attrValues', 'title');

        return $this->book;
    }

    public function SaveBook()
    {
        if (!is_array($this->book) || empty($this->book['clear_name'])) return false;

        $this->SaveAuthor();
        $this->SaveSerie();
        $this->SaveB();

        if (!empty($this->book['id'])) {
            $this->SavePubHouses();
            $this->SaveReviews();
            $this->SaveMappingLinks();
            $this->SaveTags();
            $this->SaveImageUrls();
        }

        $this->AddBooks2Crawl(json_decode($this->book['similar_books']));
        return true;
    }
}