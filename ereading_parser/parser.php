<?php
/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:55
 */

include_once '../config.php';
include_once 'ereading.class.php';
include_once '../library/db.class.php';
$db = new db();
$bp = new EreadingParser($db);
//echo extension_loaded('tidy') ? "LOADED" : "NOT LOADED";
$sql = 'SELECT max(id) FROM ereading_urls WHERE type="book" AND status=1';
$max_id = $db->select($sql, 'value');

$cur_id = 0;
$step = 1000;
//$i=7;
do
{
//    $sql = "truncate books; truncate mapping_links; truncate pub_houses; truncate reviews";
//    $db->exec($sql);

    $sql = 'SELECT id FROM ereading_urls WHERE type="book" AND  status=1 AND id >' . $cur_id . ' LIMIT ' . $step;
    //$sql = 'SELECT id FROM ereading_urls WHERE id=105';

    $url_ids = $db->select($sql, 'array');
    if(!empty($url_ids))
    {
        foreach($url_ids AS $url_id)
        {
            $cur_id = $url_id;
            $book = $bp->GetBookInfo($url_id);
            $bp->SaveBook();
            //var_dump($book); die();

            if($bp->SaveBook())
                $bp->ChangeUrlStatus($url_id, '2');
            else
                $bp->ChangeUrlStatus($url_id, '3');
        }
    }
    else
        $cur_id += $step;



}while($cur_id<$max_id);

