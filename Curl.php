<?php
namespace gzh;

class Curl
{

    /**
     * 数组转化成请求字符串
     * @param mixed $params 表单参数
     */
    public static function makeQueryString($params, $is_en = 0 , $method = 'get')
    {
        if (is_string($params))
            return $params;
        if ('JSON' == strtoupper($method))
            return json_encode($params, JSON_UNESCAPED_UNICODE);
            
        $query_string = array();
        foreach ($params as $key => $value)
        {   $v = $is_en ? urlencode($value) : $value;
            array_push($query_string, $key . '=' . $v);
        }   
        $query_string = join('&', $query_string);
        return $query_string;
    }

    /**
     * 执行一个 HTTP 请求
     *
     * @param string    $url    执行请求的URL 
     * @param mixed     $params 表单参数 (array / string)
     * @param string    $method 请求方法 post / get / post josn
     * @param boole     $is_en 是否将参数urlencode
     * @param string    $protocol http协议类型 http / https
     * @param int       $max_time 请求响应的最长时间(秒)
     * @param boole     $print  请求响应的最长时间(秒)
     * @return mixed    返回结果集
     */
    public static function makeRequest($url, $params, $method='get', $print=FALSE,
        $is_en=FALSE, $protocol='http', $max_time = 10, $headerArr=array())
    { 
        if(substr(ltrim($url), 0, 5) == "https") $protocol = 'https';  
        if(isset($params['is_echo']) && $params['is_echo'] == 'ky_echo') {
            $print = TRUE;
            unset($params['is_echo']);
        }
        $query_string = self::makeQueryString($params, $is_en, $method);
        //http_build_query — 自动URL-encode，所以不能直接用 
          
        $ch = curl_init();

        if ('GET' == strtoupper($method))
        {
            curl_setopt($ch, CURLOPT_URL, "$url?$query_string");
        }
        else //POST || POST_JOSN
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);//传递一个作为HTTP “POST”操作的所有数据的字符串//也可以直接使用$params(只能使用一维数据)

            if ('JSON' == strtoupper($method))
                $headerArr = [
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($query_string)
                ];
        } 

        curl_setopt($ch, CURLOPT_HEADER, FALSE);//设定是否输出页面内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);//curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//在发起连接前等待的时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $max_time);//设置cURL允许执行的最长秒数。

        $headerArr = $headerArr ? $headerArr : array('Expect:');//默认解决 curl出现Expect:100-continue
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);//设置一个header中传输内容的数组

        if ('https' == strtolower($protocol))
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        
        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){//设置curl默认访问为IPv4
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $result = curl_exec($ch);

        if($print) {
            var_dump("$url?$query_string");
            var_dump($result);
            var_dump(curl_error($ch));
        }
    
        curl_close($ch);

        return $result;
                
    }
}