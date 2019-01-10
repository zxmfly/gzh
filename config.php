<?php
define('ACCESS_TOKEN_CKEY', 'gzh_access_token');//缓存key
$WxGzhConfig = [
	'appid' => 'wx99707df75650dd15',
	'secret' => '0cb62adddca5c405fbf73b3917fab35e',
	'token' => 'zxmgzh123',
];

$MemacheConfig = [
	'key_prefix' => 'zxmMemcachePref',
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