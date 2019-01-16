<?php
date_default_timezone_set('Asia/Shanghai');
include './config.php';
include './functions.php';
include './Curl.php';
include './Mysql.php';
include './Memcache.php';
include './GaoDe.php';
include './Gzh.class.php';

//首先要验证token
//1收到微信消息，解析并判断，消息类型
use gzh\GzhClass;
use gzh\MemcacheClass;
use gzh\Mysql;
use gzh\Curl;
use gzh\GaoDe;

global $memcache, $mysql;
if(!$memcache) {
	$memcache =  new MemcacheClass();
	$memcache->connect($MemacheConfig);
}

$gzh = new GzhClass($WxGzhConfig);
