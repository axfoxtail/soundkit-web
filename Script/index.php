<?php
session_start();
error_reporting(-1);
//header('Access-Control-Allow-Origin: *');
ini_set('memory_limit', '-1'); // unlimited memory limit
date_default_timezone_set('UTC');
//exit(md5('250.0012rhz4c'));
//exit(date('y m d h:s:i a', '1572566400'));
/**$lastWeek = strtotime('first day of January 2018');
exit(date('y m d h:s:i a', $lastWeek));
/**
 * Important Define Constants
 */
define("APP_BASE_PATH", __DIR__.'/');


define("VERSION", '2.4.2');

//exit(file_get_contents('http://streaming.radionomy.com/JamendoLounge?lang=en-US%2cen%3bq%3d0.9'));
include_once "app/request.php";
include_once "app/vendor/utils.php";

Request::instance()->start();
