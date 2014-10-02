<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:03
 */
Class BaseParser
{
    protected $conn;
    protected $project;
    protected $cache_path;
    protected $book = array();

    public function __construct($project, $cache_path, $conn)
    {
        $this->conn = $conn;
        $this->project = $project;
        $this->cache_path = $cache_path;
    }
    public function __set($name, $value)
    {
        return $this->$name = $value;
    }

    protected function GetPath2Cache($book_id)
    {
        $this->url_id = $book_id;
        $fc = substr($book_id, strlen($book_id) - 1, 1);
        $sc = strlen($book_id) > 1 ? substr($book_id, strlen($book_id) - 2, 1) : 0;
        echo $root_path = $this->cache_path . $sc . '/' . $fc . '/' . $book_id . '.html';
        //$root_path .= ($this->project == 'livelib') ? '.qz' : '.html';
        return $root_path;
    }

    protected function SaveDownloadLinks()
    {
        if (!empty($this->book['download_links'])) {

            for ($i = 0; $i < count($this->book['download_links']); $i++) {

						$this->book["download_dates"][$i] = !empty($this->book["download_dates"][$i]) ? $this->book["download_dates"][$i] : '';

                $sql = "INSERT INTO " . $this->project  . "_download_links(url, format, date, size, book_id, `from`)
                        VALUES('" . $this->book["download_links"][$i] . "','" . $this->book["download_formats"][$i] . "',
                        '" . $this->book["download_dates"][$i] . "', '" .
						$this->book["download_sizes"][$i] . 
						"', {$this->book['id']}, '" .
						$this->project. "')";
                $this->conn->exec($sql);

                $sql = "INSERT IGNORE INTO download_links(url, format, date, size, book_id, `from`)
                        VALUES('" . $this->book["download_links"][$i] . "','" . $this->book["download_formats"][$i] . "',
                        '" . $this->book["download_dates"][$i] . "', '" .
                    $this->book["download_sizes"][$i] .
                    "', {$this->book['id']}, '" .
                    $this->project. "')";
                $this->conn->exec($sql);

            }
        }
    }

    public function ChangeUrlStatus($id, $status)
    {
        $sql = 'UPDATE ' . $this->project . '_urls SET status=' . $status . ' WHERE id= ' . $id;
        $this->conn->exec($sql);
    }

    protected function GetHtml($book_id)
    {
        $html = '';
        $cache_path = $this->GetPath2Cache($book_id);
        if (file_exists($cache_path)) {
            $html = file_get_contents($cache_path);
           // $html = ($this->project == 'livelib') ? gzdecode($content) : $content;
        }

        return $html;
    }


    protected function GetPregRes($html, $pattern, $type = 'first')
    {
        //echo $html;
        if ($type == 'all') {
            preg_match_all($pattern, $html, $m);

        } else
            preg_match($pattern, $html, $m);

        return !empty($m[1]) ? $m[1] : '';
    }


    protected function SaveImageUrls()
    {
        if (empty($this->book['img_url'])) return;
        foreach ($this->book['img_url'] AS $url) {
            $sql = "INSERT IGNORE INTO " . $this->project . "_image_urls (book_id, url)
                      VALUES(" . $this->book['id'] . ",'" . $url . "')";
            $this->conn->exec($sql);

            $sql = "INSERT IGNORE INTO image_urls (book_id, url, `from`, status)
                      VALUES(" . $this->book['id'] . ",'" . $url . "', '" . $this->project . "', 0)";
            $this->conn->exec($sql);

        }
    }

    protected function SaveReviews()
    {
        if (!empty($this->book['reviews_descr'])) {
            for ($i = 0; $i < count($this->book['reviews_descr']); $i++) {
			
			$this->book['comment_rating'][$i] = !empty($this->book['comment_rating'][$i]) ? $this->book['comment_rating'][$i] : 0;
			$this->book['reviews_author'][$i] = !empty($this->book['reviews_author'][$i]) ? $this->book['reviews_author'][$i] : 0;
			
                $sql = "INSERT IGNORE INTO " . $this->project  . "_reviews(book_id, author, description, date_published, rating, project, md5)
                        VALUES({$this->book["id"]}, '". $this->conn->real_escape_string( $this->book['reviews_author'][$i])  ."', '" . $this->conn->real_escape_string($this->book['reviews_descr'][$i]) .
                    "', '" . $this->book['date_published'][$i] . "', '" . intval($this->book['comment_rating'][$i]) . "', '"  . $this->project . "',
                     '" . md5($this->book['reviews_descr'][$i]) . "')";
                $this->conn->exec($sql);
            }
        }
    }

    protected function ClearIsbn($isbn)
    {
        $pattern1 = '~\d+[—-]\d+[—-]\d+[—-]\d+[—-][\dХX]+~';
        $pattern2 = '~\d+[—-]\d+[—-]\d+[—-][\dХX]+~';

        preg_match_all($pattern1, $isbn, $m);
        $isbn_arr = !empty($m[0]) ? $m[0] : '';

        if (empty($isbn_arr)) {
            preg_match_all($pattern2, $isbn, $m);
            $isbn_arr = $m[0];
        }


        return implode(', ', $isbn_arr);
    }

    protected function SaveAuthor()
    {
        $this->book['author_ids'] = array();
        $authors = explode(', ', $this->book['author']);
        foreach ($authors AS $author) {
            $hash = $this-> clearString($author);

            $sql = "SELECT id FROM author WHERE hash='" . $hash . "'";
            $id = $this->conn->select($sql, 'value');
            if (empty($id)) {
                $sql = "INSERT IGNORE INTO author (name, type, biography, hash)
                          VALUES('" . $this->ClearBr($author) . "','writer', '', '" . $hash . "')";
                $id = $this->conn->exec($sql, 'inserted_id');
            }
            $this->book['author_ids'][] = $id;
        }

        $this->book['author_id_str'] = implode(',',$this->book['author_ids']);
    }

    protected function bindAuthors2book()
    {
        foreach ($this->book['author_ids'] AS $author_id) {
            $sql = "INSERT IGNORE INTO " . $this->project . "_ba_bind(book_id, author_id) VALUES( " . $this->book['id'] . ", $author_id)";
            $this->conn->exec($sql);
        }
    }

    protected function SaveB()
    {
        $sql = "SELECT * FROM " . $this->project . "_books WHERE clear_name='" . $this->book['clear_name'] . "'";
        $book = $this->conn->select($sql, 'arow');
//
        if (!empty($book)) {
            //проверяем ISBN
            if (!empty($book['isbn']) && !empty($this->book['isbn'])) {
                $isbn_arr1 = explode(', ', strtolower($book['isbn']));
                $isbn_arr2 = explode(', ', strtolower($this->book['isbn']));

                $arr_intersect = array_intersect($isbn_arr1, $isbn_arr2);

                if (!empty($arr_intersect)) {
                    $book_id = $book['id'];
                   // $this->SaveAuthorAliases($book);
                }
            }
            //если ISBN разные, то проверяем авторов
            elseif ($this->CheckAuthors($book)) {
                $book_id = $book['id'];
            }
        }

//        if (!empty($book)) {
//            $book_id = $book['id'];
//        }

        if (empty($book_id)) {
            $sql = "INSERT INTO " . $this->project . "_books(name, clear_name, isbn, series_id, author_ids, description, page_num, info, url_id, `from`) VALUES('" . $this->ClearBr($this->book['name']) . "','{$this->book['clear_name']}', '" . $this->book['isbn'] . "',{$this->book['serie_id']}, '{$this->book['author_id_str']}', '{$this->book['description']}', 0, '{$this->book['info']}', {$this->book['url_id']}, '{$this->project}')";
            $book_id = $this->conn->exec($sql, 'inserted_id');
        }

        $this->book['id'] = $book_id;
        $this->bindAuthors2book();
    }

    public function AppendBookData($cur_book, $new_book)
    {
        //isbn, publisher,description
        $isbn = empty($cur_book['isbn']) && !empty($new_book['isbn']) ? $new_book['isbn'] : 0;
        $description = empty($cur_book['description']) && !empty($new_book['description']) ? $new_book['description'] : 0;

        if ($isbn || $description) {
            $sql = 'UPDATE " . $this->project . "_book SET ';
            if ($isbn && $description) {
                $sql .= "isbn='$isbn', description='$description'";
            } elseif ($description) {
                $sql .= "description='$description'";
            } elseif ($isbn) {
                $sql .= "isbn='$isbn'";
            }

            $this->_conn->exec($sql);
        }
    }

//    private function SaveAuthorAliases($book)
//    {
//        $sql = "SELECT author_id FROM book_authors_bind WHERE book_id =" . $book['id'];
//        $authors1 = $this->conn->select($sql, 'array');
//        $authors2 = $this->book['author_ids'];
//
//        if(count($authors1) == 1 && count($authors2) == 1 && $authors1[0] !=  $authors2[0])
//        {
//            $sql = "INSERT IGNORE INTO author_aliases(author1_id, author2_id) VALUES( $authors1[0], $authors2[0])";
//            $this->conn->exec($sql);
//        }
//    }

    private function CheckAuthors($book)
    {
        //получаем авторов текущей книги
        //$sql = "SELECT author_id FROM book_authors_bind WHERE book_id =" . $book['id'];
        $authors1 = explode(',', $book['author_ids']);
        $authors2 = $this->book['author_ids'];

        $union = array_merge($authors1, $authors2);
        $inter = array_intersect($authors1, $authors2);
        //если хотя бы один автор одинаковый - книги одинаковые
        if (!empty($inter)) return true;

//        //проверяем авторов на предмет алиасов
//        $sql = "SELECT * FROM author_aliases WHERE author1_id IN(" . implode(',', $union) . ") OR
//                        author2_id IN(" . implode(',', $union) . ")";
//        $rows = $this->conn->select($sql, 'arows');
//
//        $aurhors_arr = array();
//        foreach ($rows AS $row) {
//            $aurhors_arr[$row['author1_id']][] = $row['author2_id'];
//            $aurhors_arr[$row['author2_id']][] = $row['author1_id'];
//        }
//
//
//        foreach ($authors1 AS $auth_id) {
//            $alias_arr = !empty($aurhors_arr[$auth_id]) ? $aurhors_arr[$auth_id] : array();
//            $inter = array_intersect($alias_arr, $authors2);
//            if (!empty($inter)) return true;
//        }

        return false;
    }

    public function SaveMappingLinks()
    {
        if (empty($this->book['shop_links'])) return;
        foreach ($this->book['shop_links'] AS $url) {
            $url = urldecode($url);
            //$url = str_replace('http://www.livelib.ru/go/http%3A/%252F', 'http://', $url);
            preg_match('~http://www.livelib.ru/go/http:/%2F(.*)(\?from|\?p|\?refere|%26utm_source|%26lfrom=|\?ref_partner)~i', $url, $m);

            if (!empty($m[1])) {
                $url = 'http://' . $m[1];

                $sql = "INSERT IGNORE INTO livelib_shop_links (book_id, url)
                      VALUES(" . $this->book['id'] . ",'" . $url . "')";
                $this->conn->exec($sql);
            }
        }
    }

    protected function clearString($text)
    {
        $text = str_replace(array("\\r\\n", "\\r", "\\n"), '' , htmlspecialchars_decode($text));
        $text = preg_replace("~[^а-яa-z0-9]+~uis", '', $text);
        $text = mb_strtolower($text, mb_detect_encoding($text));
        return $text;
    }

	protected function ClearBr($text)
	{
	 return $this->conn->real_escape_string(trim(str_replace(array("\\r\\n", "\\r", "\\n"), ' ' , $text)));
	 
	}
	
    public function SavePubHouses()
    {
	if(!empty($this->book['pub_house']))
        foreach ($this->book['pub_house'] AS $pub_house) {
            $clear_pub_house = $this->clearString($pub_house);
            $sql = "SELECT id FROM pub_houses WHERE clear_pub_house='" . $clear_pub_house . "'";
            $id = $this->conn->select($sql, 'value');
            if (empty($id)) {
                $sql = "INSERT IGNORE INTO pub_houses (name, clear_pub_house)
                      VALUES('" . $this->ClearBr($pub_house) . "', '" . $clear_pub_house . "')";
                $id = $this->conn->exec($sql, 'inserted_id');
            }

            $sql = "INSERT IGNORE INTO " . $this->project . "_ph_bind(book_id, pub_house_id) VALUES( " . $this->book['id'] . ", $id)";
            $this->conn->exec($sql);
        }
    }

    public function SaveSerie()
    {
        if(!empty($this->book['serie']))
        {
            $clear_serie = $this->clearString($this->book['serie']);
                $sql = "SELECT id FROM series WHERE clear_name='" . $clear_serie . "'";
                $id = $this->conn->select($sql, 'value');
                if (empty($id)) {
                    $sql = "INSERT IGNORE INTO series (name, clear_name)
                      VALUES('" . $this->ClearBr($this->book['serie']) . "', '" . $clear_serie. "')";
                    $id = $this->conn->exec($sql, 'inserted_id');
                }
        }
        $this->book['serie_id'] = !empty($id) ? $id : 0;
    }


    public function SaveGenres()
    {
        for ($i = 0; $i < count($this->book['genres']); $i++) {
            $clear_genre = $this->clearString($this->book['genres'][$i]);
            if (empty($clear_genre)) continue;

            $sql = "SELECT id FROM genres WHERE clear_name='" . $clear_genre . "'";
            $id = $this->conn->select($sql, 'value');

            if (empty($id)) {
                $sql = "INSERT IGNORE INTO genres (name, clear_name)
                      VALUES('" . $this->ClearBr($this->book['genres'][$i]) . "', '$clear_genre')";
                $id = $this->conn->exec($sql, 'inserted_id');
            }

            $sql = "INSERT IGNORE INTO " . $this->project . "_bg_bind(book_id, genre_id) VALUES( " . $this->book['id'] . ", $id)";
            $this->conn->exec($sql);
        }
        return false;
    }

    public function SaveTags()
    {
        for ($i = 0; $i < count($this->book['genres']); $i++) {
            $clear_name = $this->clearString($this->book['genres'][$i]);

            if(empty($clear_name)) continue;

            if(!empty($this->book['genres_urls'][$i]))
            {
                $this->AddtoCrawl($this->conn->real_escape_string($this->book['genres_urls'][$i]),0, 'tag');
            }

            $sql = "SELECT id FROM tags WHERE clear_name='" . $clear_name . "'";
            $id = $this->conn->select($sql, 'value');

            if (empty($id)) {
                $sql = "INSERT IGNORE INTO tags (name, clear_name)
                      VALUES('" . $this->ClearBr($this->book['genres'][$i]) . "', '$clear_name')";
                $id = $this->conn->exec($sql, 'inserted_id');
            }

            $sql = "INSERT IGNORE INTO " . $this->project . "_bt_bind(book_id, tag_id) VALUES( " . $this->book['id'] . ", $id)";
            $this->conn->exec($sql);
        }
        return false;
    }

    public function AddBooks2Crawl($urls)
    {
        foreach ($urls AS $url) {
            $sql = "INSERT IGNORE INTO  " . $this->project . "_urls (url, md5, type, date, status)
                      VALUES('" . $url . "', '" . md5($url) . "', 'book', NOW(), '0')";
            $this->conn->exec($sql);
        }
    }

    public function AddtoCrawl($url, $parent_id,  $type)
    {
        $https = array('http://livelib.ru');
        $url = str_replace($https, '',$url);

        $sql = "INSERT IGNORE INTO "  . $this->project . "_urls (url, md5, parent_id, type, date, status)
                      VALUES('" . $url . "', '" . md5($url) .  "', " . $parent_id . ", '" . $type . "', NOW(), '0')";

        return $this->conn->exec($sql, 'inserted_id');
    }

    public function XpathNodeValue($xpath, $type = 'nodeValue', $attribute = '')
    {
        $result = null;
        $result = null;
        $res = $this->xpath->query($xpath);
        //var_dump($this->xpath, $xpath,$res);
        if ($res->length > 0) {
            switch ($type) {
                case 'nodeValues':
                {
                    $result = array();
                    foreach ($res AS $item) {
                        $result[] = $item->nodeValue;
                    }
                    break;
                }
                case 'attrValue':
                {
                    $result = $res->item(0)->getAttribute($attribute);
                    break;
                }
                case 'attrValues':
                {
                    $result = array();
                    foreach ($res AS $item) {
                        $result[] = $item->getAttribute($attribute);
                    }
                    break;
                }
                case 'key_value':
                {
                    $result = array();
                    foreach ($res AS $item) {
                        $result[] = array($item->nodeValue, $item->getAttribute($attribute));
                    }
                    break;
                }

                default:
                    {
                    $result = $res->item(0)->nodeValue;
                    }
            }
        }
        return $result;

    }

}