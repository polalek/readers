<?php
/**
 * Created by PhpStorm.
 * User: alexeyp
* Date: 30.06.14
* Time: 0:11
*/
ini_set( 'default_charset', 'UTF-8' );
libxml_use_internal_errors(true);

define('ROOT_PATH', '/home/apolunin/ar/trunk/livelib_crawler/');
define('CACHE_PATH', '/media/4dc448cb-d777-4392-b22b-99992ed5aea6/cache/livelib/');

include '../library/BaseCrawler.php';
include_once '../library/db.class.php';
include  'GenerParser.php';
$db = new db();
$gp  = new GenerParser($db);
$gp->getBookReviews();