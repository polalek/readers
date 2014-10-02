<?php
/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 05.08.14
 * Time: 15:47
 */

ini_set( 'default_charset', 'UTF-8' );
libxml_use_internal_errors(true);

define('ROOT_PATH', '/home/apolunin/ar/trunk/litmir_crawler/');
define('CACHE_PATH', '/media/4dc448cb-d777-4392-b22b-99992ed5aea6/cache/litmir/');


include  '../library/BaseCrawler.php';
include_once '../library/db.class.php';
include_once 'getSitemap.php';
$db = new db();
$gs  = new GetSitemap($db);
$gs->DetectBooks();