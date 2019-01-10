<?php

/**
 * 获取memcache
 * @param $key
 */
function getCache($key){
    global $memcache;
    $key = md5($key);
    $rs = $memcache->get($key);
    return $rs;
}

/**
 * 获取memcache
 * @param string $key
 * @param mixed $value
 * @param int $ttl
 */
function setCache($key, $value, $ttl=-1){
    global $memcache;
    $key = md5($key);
    if($ttl > 0){
        $memcache->set($key, $value, false, $ttl);
    }
    else{
        $memcache->set($key, $value);
    }
    return true;
}

/*
* 获取 $_SERVER['QUERY_STRING'] 原始数据
* parse_str,GET 会自动urldecode,对于“+”会变成空格
* $_SERVER['QUERY_STRING'] = "first=value&arr=foo+bar&arr1=baz";
*/
function parseQueryString(){ 
    $op = []; 
    $pairs = explode("&", $_SERVER['QUERY_STRING']); 
    foreach ($pairs as $pair) { 
        list($k, $v) = explode("=", $pair); 
        $op[$k] = $v; 
    } 
    return $op; 
}

/**
 * 返回充值回调请求参数,并记录请求url日志
 * @m 获取数据的方法，默认为空，默认使用$_REQUEST
 *    str 使用QUERY_STRING方法获取GET原始数据，避免原始数据被urldecode
 *    json 使用HTTP_RAW_POST_DATA等获取输入流(json)数据，并返回json_decode
 *    origin 使用HTTP_RAW_POST_DATA等获取输入流数据，不做任何操作
 */
function getInputData($m = ''){
    $data = [];
    if($m == 'str'){
        $data = parseQueryString();
    }elseif($m == 'json'){
        $command = file_get_contents("php://input") ? file_get_contents("php://input") : $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = json_decode($command, true);//true,转化成数组
    }elseif($m == 'origin'){
        $data = file_get_contents("php://input") ? file_get_contents("php://input") : $GLOBALS['HTTP_RAW_POST_DATA'];
    }elseif($m){
        $data = strtolower($m) == 'post' ? $_POST : $_GET;
    }else{
    	$data = $_REQUEST;
    }
    return $data;
}

/**
 * 日志
 *
 */
function writeLog($str = ''){
    file_put_contents("./gzh.log", date("Y-m-d h:i:s")." {$str}\r\n", FILE_APPEND);  
}

function wwwLog($str = ''){
    if(empty($str)){
        $str = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $str .= $_REQUEST ? '?'.http_build_query($_REQUEST) : '';
    }

    error_log(date("Y-m-d h:i:s")." {$str}\r\n",3,'./www_test.log');
}