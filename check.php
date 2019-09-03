<?php
//网页授权验证
getUserCode();

exit;
//网页授权
	function getUserCode($scope = 'snsapi_base'){//snsapi_userinfo
			$appid = 'wx7b5d94d8489b08fd';
			$secret = '0bd2c0dd0dba703a5acca2ea0481169c';
			$back_url = "https://mlsgzm.geiniwan.com/gzh/";
			$back_url .= $scope == 'snsapi_base' ? "baseInfo.php" : "userInfo.php";
			$back_url = urlencode($back_url);
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$back_url}&response_type=code&scope={$scope}&state=zxm_test#wechat_redirect";
			header("location:".$url);
	}

	function getUserBase($data){//获取基础信息
		$user_base = getUserInfoToken($data['code']);
		echo "个人基础信息:".json_encode($user_base);
	}

	function getUserInfo($data){//获取详细信息
		$user_base = getUserInfoToken($data['code']);
		$access_token = $user_base['access_token'];
		$openid = $user_base['openid'];

		$url = "https://api.weixin.qq.com/sns/userinfo?access_token={access_token}&openid={$openid}&lang=zh_CN";
		$rs = curlGet($url);

		echo "个人详细信息:".$rs;
	}

	function getUserInfoToken($code){

		$appid = 'wx7b5d94d8489b08fd';
		$secret = '0bd2c0dd0dba703a5acca2ea0481169c';
		$data = [
			'appid' => $appid,
			'secret' => $secret,
			'code' => $code,
			'grant_type' => 'authorization_code',
		];
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token";
		$re = curlGet($url, 0, 60, $data);
		$rs = json_decode($re, 1);

		return $rs;
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