<?php
date_default_timezone_set('Asia/Shanghai');
include './config.php';
include './functions.php';
include './Curl.php';
include './Mysql.php';
include './Memcache.php';
include './GaoDe.php';
include './Gzh.class.php';

echo json_encode([
		'expire_seconds' => 86400*30,//有效期，默认，30秒，最大30天
		'action_name' => 'QR_SCENE',//二维码类型
		'action_info' => [
			'scene_id' => 1001,//（目前参数只支持1--100000）场景值，哪个二维码场景入口
			'scene_str' => '生成临时二维码',//字符串类型，长度限制为1到64
			],
	]);
die;
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

//测试下远程仓库
