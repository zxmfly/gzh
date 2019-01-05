<?php
namespace gzh;
class GzhClass
{	
	public $token = '';
	public $msgType = '';
	public $event = '';
	public $content = '';
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
		$this->content = strtolower($obj->Content);
		$replyData['touser'] = $obj->FromUserName;
		$replyData['fromuser'] = $obj->ToUserName;

		//消息类型
		if($this->msgType == 'event'){
			//事件类型
			if($this->event == 'subscribe'){
				$str = "账号：{$replyData['touser']}, 关注";
				writeLog($str);
				$replyData['content'] = '你好！我是ZXM，欢迎你关注我的公众号！';
				self::printXmlText($replyData);
			}else{
				$str = "账号：{$replyData['touser']}, 取消关注";
				writeLog($str);
			}
		}elseif($this->msgType == 'text'){
			if(is_numeric($this->content)) {
				$content = "你输入的是数字:".$this->content;
				$this->content = '数字';
			}
			switch($this->content){
				case '你好':
					$content = '你好！我是zxm,很高兴认识你,今天依然要保持微笑哟~';
					break;
				case 'hello':
					$content = 'Hello, it was a pleasure seeing you.';
					break;
				case '姓名':
					$content = '你好！我叫峻峻，很高兴认识你！';
					break;
				case '年龄':
					$content = '虽然我是男人，但是我也不会告诉你已经30多岁咯~';
					break;
				case '爱你':
					$content = '爱你，爱你，爱着你！就是爱你！';
					break;
				case '数字':
					break;
				default:
					$content = '你输入的是:'.$this->content.'，对不起，我不太懂，我还在完善中，你可以试试问姓名、年龄、爱你...';
					break;
			}
			$replyData['content'] = $content;
			self::printXmlText($replyData);
		}else{
			$replyData['content'] = "模块还在建设中，敬请期待！";
			self::printXmlText($replyData);
		}
	}

	function printXmlText($data){
		$touser = $data['touser'];
		$fromuser = $data['fromuser'];
		$ctime = time();
		$mtype = 'text';
		$content = $data['content'];

		$template = "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[%s]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";

		$info = sprintf($template, $touser, $fromuser, $ctime, $mtype, $content);
		echo $info;
		return;
	}

}