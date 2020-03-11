<?php
define('ACCESS_TOKEN_CKEY', 'gzh_access_token');//token缓存key
define('WX_IP_LIST_CKEY', 'gzh_wx_server_ip');//ip缓存key

$WxGzhConfig = [//我的账号，未认证订阅号，权限少
	'appid' => 'wx99707df75650dd15',
	'secret' => '0cb62adddca5c405fbf73b3917fab35e',
	'token' => 'zxmgzh123',
];

$WxGzhConfig = [//微信测试账号，基本的权限，接口都能用
	'appid' => 'wx7b5d94d8489b08fd',
	'secret' => '0bd2c0dd0dba703a5acca2ea0481169c',
	'token' => 'zxmgzh123',
];

$MemacheConfig = [
	'key_prefix' => 'zxmMemcachePref_x1',
	'host' => '127.0.0.1', 
	'port' => 11211,
];

$MysqlConfig = [
	'HOST' => '127.0.0.1', 
	'PORT' => 3306,
	'USER' => 'root',
	'PASSWORD' => 'zxmmysqlrootpwd',
	'DBNAME' => 'gzh',
	'CHARSET' => 'utf8',
];

$MysqlConfigLocal = [
	'HOST' => '127.0.0.1', 
	'PORT' => 3301,
	'USER' => 'root',
	'PASSWORD' => '',
	'DBNAME' => 'gzh',
	'CHARSET' => 'utf8',
];

function getMysqlConf(){
	global $MysqlConfigLocal, $MysqlConfig;

	$config = $MysqlConfigLocal;

	return $config;
}

//高德地图配置
function gaoDeConfig(){
	return [
		'key_name' => 'gzh_key',
		'key' => '90b6a30424a81794c24ccc6db280722b',
		'output' => 'JSON',
	];
}

/*
注意，个人订阅号是不能生成二维码的

QR_SCENE => scene_id
QR_STR_SCENE => scene_str

二维码类型:
QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
*/
function QrcodeConfig(){
	return [
		'expire_seconds' => 86400*7,//有效期，默认，30秒，最大30天
		'action_name' => 'QR_SCENE',//二维码类型 QR_LIMIT_SCENE 永久
		'action_info' => [
			'scene'=>[
				'scene_id' => 1001,//（目前参数只支持1--100000）场景值，哪个二维码场景入口 2001 永久
			]
		],
	];
}