<?php
Class BaseCrawler
{
    protected $_conn;
    protected $last_http_code;
    private $ports = array('9050', '9051', '9052', '9053', '9054', '9055', '9056', '9057', '9058', '9059');

    public function __construct($db) {
        $this->_conn = $db;

    }

    public function save_url($url, $type, $status) {
        $sql = "INSERT IGNORE INTO " . $this->project . "_urls (url, md5, type, date, status)
                      VALUES('" . $url . "', '" . md5($url) .  "', '" . $type . "', NOW(), '" . $status . "')";
        return $this->_conn->exec($sql, 'inserted_id');
    }

    public function get_html($url, $cookie = '')
    {
        echo 'STARTING get_html, url: ' . $url . PHP_EOL;

         $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,         // return web page
          //  CURLOPT_PROXY => 'socks5://127.0.0.1:' . $this->ports[rand(0,9)],
             //CURLOPT_PROXY => 'socks5://127.0.0.1:9050',
            CURLOPT_USERAGENT      => "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36",     // who am i
            CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
            CURLOPT_TIMEOUT        => 120,          // timeout on response
			CURLOPT_COOKIE			=> $cookie,
			CURLOPT_FOLLOWLOCATION => 1,
        );
        //var_dump($options); die();
        $ch = curl_init( );
        curl_setopt_array($ch, $options);
        $html =  curl_exec($ch);
        $err = curl_error($ch);
        $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo 'HTTP code:' .$this->last_http_code . ';  DONE get_html' . PHP_EOL;

        if(empty($html))
        {	
            echo 'Empty html, sleeping...' . PHP_EOL;
			var_dump($err);
            sleep(5);

            $this->get_html($url);
        }

        return $html;
    }


    public function save_html($url_id, $html, $type='html`') {
        if(empty($html)) return;
        $fc = substr($url_id, strlen($url_id)-1, 1);
        $sc = strlen($url_id) > 1 ?  substr($url_id,strlen($url_id)-2, 1) : 0;
        $root_path = CACHE_PATH . $sc . '/';
        if (!is_dir($root_path))
            mkdir($root_path);

        $root_path .= $fc . '/';
        if (!is_dir($root_path))
            mkdir($root_path);
        echo 'saving html to: ' . $root_path . '/' . $url_id . '.html' . PHP_EOL;
		if($type=='qz')$html = gzencode($html);
		
        return file_put_contents($root_path . '/' . $url_id . ($type=='qz'? '.qz' : '.html'), $html);
    }

//
//    public function GetBookCache()
//    {
//        while(true){
//            $sql = 'SELECT * FROM crawl_urls WHERE type = "book" AND status = 0';
//            $res = $this->_conn->query($sql);
//
//            $books =  $res->fetch_all(MYSQL_ASSOC);
//            foreach($books AS $book)
//            {
//                $url = 'http://www.litmir.net/' . $book['url'];
//                $html = $this->get_html($url);
//
//                if(!empty($html))
//                {
//                    $this->ChangeUrlStatus($book['id'], 1);
//                    $this->save_html($book['id'], $html);
//                }
//            }
//
//            if(empty($books)) sleep(10);
//        }
//
//    }

	public function GetUrlId($url)
	{
		$sql = "SELECT id FROM " . $this->project . "_urls WHERE md5='" . md5($url) . "'";
		return $this->_conn->select($sql, 'value');
		
	}


    public function AddtoCrawl($url, $parent_id,  $type)
    {
        $https = array('http://livelib.ru');
        $url = trim(str_replace($https, '',$url));

        $sql = "INSERT IGNORE INTO "  . $this->project . "_urls (url, md5, parent_id, type, date, status)
                      VALUES('" . $url . "', '" . md5($url) .  "', " . $parent_id . ", '" . $type . "', NOW(), '0')";
				
        return $this->_conn->exec($sql, 'inserted_id');
    }

    public function ChangeUrlStatus($id, $http_code, $status)
    {
        $sql = 'UPDATE '  . $this->project . '_urls SET status=' . $status;
        if(!empty($http_code))
        {
            $sql .=  ", httpcode='" . $http_code . "'";
        }

        $sql .= ' WHERE id= '. $id ;
        $this->_conn->exec($sql);
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
                    foreach($res AS $item)
                    {
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

