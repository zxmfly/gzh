<?php
date_default_timezone_set('Asia/Shanghai');
include './config.php';
include './functions.php';
include './Curl.php';
include './Mysql.php';
include './Memcache.php';
include './Wx.class.php';

use gzh\WxClass;
use gzh\MemcacheClass;
use gzh\Mysql;
use gzh\Curl;

global $memcache, $mysql;
if(!$memcache) {
	$memcache =  new MemcacheClass();
	$memcache->connect($MemacheConfig);
}

$path_info = $_SERVER['PATH_INFO'];
$f = $path_info ? substr($path_info, 1) : 'index';

$wx = new WxClass();
if(method_exists($wx, $f)){
    $wx->$f();
}else{
	$wx->error();
}

