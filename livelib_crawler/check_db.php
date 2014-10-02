<?php
/**
 * Created by JetBrains PhpStorm.
 * User: apolunin
 * Date: 19.09.14
 * Time: 10:14
 * To change this template use File | Settings | File Templates.
 */

include_once '../library/db.class.php';
$db = new db();

$sql = 'SELECT * FROM ar1.livelib_urls';
$rows = $db->select($sql, 'arows');

foreach($rows AS $row)
{
    $md5 = $row['md5'];
    $url = trim(str_replace(array('http://livelib.ru', 'http://www.livelib.ru'), '',$row['url']));
    $url = trim(str_replace('//', '/',$url));

    $sql = 'UPDATE ar.livelib_urls SET url="' . mysql_real_escape_string($url) . '" WHERE md5="' . $md5 . '"';
    $db->exec($sql);
    echo $row['id'] . "\r";
}