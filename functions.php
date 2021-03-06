<?php

function getWeek($w = '', $all = 1){
    $w = $w ? $w : date('w');
    $week = array(
        0 => "日",
        1 => "一",
        2 => "二",
        3 => "三",
        4 => "四",
        5 => "五",
        6 => "六"
    );
    $str = $all ? '星期'.$week[$w] : $week[$w];
    return $str;
}

/**
 * 获取IP
 */
function getIP(){
    if(!empty($_SERVER["HTTP_CLIENT_IP"])) $cip = $_SERVER["HTTP_CLIENT_IP"];
    else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    else if(!empty($_SERVER["REMOTE_ADDR"])) $cip = $_SERVER["REMOTE_ADDR"];
    else $cip = "";
    return $cip;
}


function checkIp($ipArr, $ip = ''){
    $ip = $ip ? $ip : getIP();

    if(in_array($ip, $ipArr)) 
        return true;

    return false;
}

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


/**
 * curl方式 get数据
 *
 * @param mix $data
 * @param string $url 全路径,如: http://127.0.0.1:8000/test
 */
function curlGet($url, $print = false, $max_time = 60, $params = array(), $protocol='http'){
    if(substr(ltrim($url), 0, 5) == "https") $protocol = 'https';
    if($params){
        $params_str = http_build_query($params);
        $connect = strpos($url, '?') ? '&' : '?';
        $url .= $connect . $params_str;
    }
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT,$max_time);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
    if ('https' == strtolower($protocol))
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    //设置curl默认访问为IPv4
    if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    $result = curl_exec($ch);
    if($print){
        var_dump($url);var_dump($result);var_dump(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}