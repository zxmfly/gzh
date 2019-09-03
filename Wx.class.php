<?php
namespace gzh;
class WxClass
{	

	public function __construct(){

	}

	public function index(){
		echo "你好！";
	}

	public function error(){
		echo "你访问路径错误！";
	}

	//查看关于我们 -- 1、以snsapi_base为scope发起的网页授权，是用来获取进入页面的用户的openid的，并且是静默授权并自动跳转到回调页的。用户感知的就是直接进入了回调页（往往是业务页面）
	public function baseInfo(){
		echo "这里是在去往PHP工程师路上行走了7年的老马奴，虽然越来越跟不上时代的变迁，但却也不敢随意停下脚步，只能一努力的走下去……";
		
	}
	//获取用心信息 -- 2、以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的。但这种授权需要用户手动同意，并且由于用户同意过，所以无须关注，就可在授权后获取该用户的基本信息。
	public function userInfo(){
		echo '欢迎访问用户中心';

	}
	
}