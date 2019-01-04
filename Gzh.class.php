<?php
namespace gzh;
class GzhClass
{	
	public $token = '';
	public $msgType = '';
	public $event = '';
	public function __construct()
	{	
		$this->token = TOKEN;
		$data = getInputData();
		if(empty($data)) die('error');
		$tmpArr = [$this->token, $data['timestamp'], $data['nonce']];
	    sort($tmpArr, SORT_STRING);
	    $tmpStr = sha1( implode( $tmpArr ) );

	    if($data['signature'] == $tmpStr){
	    	if($data['echostr']){//第一次验证token
	    		echo $data['echostr'];
	        	exit;
	    	}else{
	    		$this->decodeMsg();
	    	}
	    }else{
	    	echo '验证失败';
	        return false;
	    }
	}

	//解析内容
	function decodeMsg(){
		//1、收到微信推送过来的消息
		$xml = getInputData('origin');
		wwwLog($xml);
		//2、处理消息类型，并设置回复类型和内容
		/*
		ToUserName	开发者微信号
		FromUserName	发送方帐号（一个OpenID）
		CreateTime	消息创建时间 （整型）
		MsgType	消息类型，event
		Event	事件类型，subscribe(订阅)、unsubscribe(取消订阅)
		*/
		$obj = simplexml_load_string($xml);
		$this->msgType = strtolower($obj->MsgType);
		$this->event = strtolower($obj->Event);
		//消息类型
		if($this->msgType == 'event'){
			//事件类型
			if($this->event == 'subscribe'){
				//回复用户消息
				$touser = $obj->FromUserName;
				$fromuser = $obj->ToUserName;
				$ctime = time();
				$mtype = 'text';
				$content = '欢迎关注我的微信公众账号！';

				$template = "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[%s]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";

				$info = sprintf($template, $touser, $fromuser, $ctime, $mtype, $content);
				echo $info;
			}
		}else{
			wwwLog('event错误');
		}
	}

}