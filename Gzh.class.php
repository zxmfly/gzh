<?php
namespace gzh;
class GzhClass
{	
	public $msgType = '';
	public $event = '';
	public $content = '';
	public $replyData = [];
	private $config = [];
	private $access_token = '';
	private $db_users = 'g_users';
	public function __construct($config)
	{	
		wwwLog();
		$this->config = $config;
		$data = getInputData();
		if(empty($data)) die('error');
		$tmpArr = [$this->config['token'], $data['timestamp'], $data['nonce']];
	    sort($tmpArr, SORT_STRING);
	    $tmpStr = sha1( implode( $tmpArr ) );

	    if($data['signature'] == $tmpStr){
	    	if($data['echostr']){//第一次验证token
	    		echo $data['echostr'];
	        	exit;
	    	}else{
	    		$this->$access_token = self::getAccessToken();
	    		$this->decodeMsg();
	    	}
	    }else{
	    	echo '验证失败';
	    }
	}


	private function WxApiHandle($type){
		$url = 'https://api.weixin.qq.com/cgi-bin/token';
		$data = [
			'appid' => $this->config['appid'],
			'secret' => $this->config['secret'],
			'grant_type' => $type,
		];
		$rs = compact('url', 'data');
		return $rs;
	}

	//access_token的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的access_token失效。
	public function getAccessToken(){
		$access_token = getCache(ACCESS_TOKEN_CKEY);

		if(!$access_token){
			$info = self::WxApiHandle('client_credential');
			$rs = Curl::makeRequest($info['url'], $info['data'], 'get', 1);
			$rs = json_decode($rs, 1);
			$access_token = $rs['access_token'];
			$cache_time = $rs['expires_in'] - 120;//提前2分钟过期
			setCache(ACCESS_TOKEN_CKEY, $access_token, $cache_time);
		}

		return $access_token;
	}

	//解析内容
	function decodeMsg(){
		//1、收到微信推送过来的消息
		$xml = getInputData('origin');
		wwwLog($xml);
		//2、处理消息类型，并设置回复类型和内容
		$obj = simplexml_load_string($xml);
		$this->msgType = strtolower($obj->MsgType);
		$this->event = strtolower($obj->Event);
		$this->content = strtolower(trim($obj->Content));
		$this->replyData['touser'] = $obj->FromUserName;
		$this->replyData['fromuser'] = $obj->ToUserName;

		//消息类型
		if($this->msgType == 'event'){

			//事件类型
			if($this->event == 'subscribe'){
				self::checkUserInfo();
				$str = "账号：".$this->replyData['touser'].", 关注";
				writeLog($str);
				$this->replyData['content'] = '你好！我是ZXM，欢迎你关注我的公众号！';
				self::printXmlText($this->replyData);
			}else{
				self::checkUserInfo('unload');
				$str = "账号：".$this->replyData['touser'].", 取消关注";
				writeLog($str);
			}

		}elseif($this->msgType == 'text'){
			//文本消息
			self::checkText();

		}else{
			$this->replyData['content'] = "模块还在建设中，敬请期待！";
			self::printXmlText($this->replyData);

		}

		return;
	}

	function checkUserInfo($type = 'in'){
		$openId = $this->replyData['touser'];

		$rs = Mysql::selectMemcache($this->db_users, ['id'], compact('openId'), 'and', '', 1800);
		if($rs) return;
		if($type == 'in'){
			$utime = time();
			Mysql::insert($this->db_users, compact('openId','utime'));
		}else{
			$unload = time();
			Mysql::update($this->db_users, compact('unload'), compact('openId'));
		}

		return;
	}

	function checkText(){
		if(is_numeric($this->content)) {
			$content = "你输入的是数字:".$this->content;
			$this->content = '数字';

		}elseif($this->content == '图文' || $this->content == 'news'){
			//图文消息个数；当用户发送文本、图片、视频、图文、地理位置这五种消息时，开发者只能回复1条图文消息；其余场景最多可回复8条图文消息 如果图文数超过限制，则将只发限制内的条数
			$items =[ 
				[
					'title' => '广州酷游',
					'desc'	=> '广州酷游官网',
					'picurl' => 'http://www.gzkuyou.com/static/img/logo.jpg',
					'url'	=> 'http://www.gzkuyou.com',
				],
				[
					'title' => '百度',
					'desc'	=> '百度一下',
					'picurl' => 'https://www.baidu.com/img/bd_logo1.png?where=super',
					'url'	=> 'https://www.baidu.com/',
				],
				[
					'title' => '腾讯',
					'desc'	=> '腾讯网首页',
					'picurl' => 'https://mat1.gtimg.com/pingjs/ext2020/qqindex2018/dist/img/qq_logo_2x.png',
					'url'	=> 'https://www.qq.com/',
				]
			];
			$this->replyData['items'] = $items;
			self::printXmlNews($this->replyData);

			return;
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
			case '百度':
				$content = "<a href='http://www.baidu.com'>百度一下</a>，你点击下试试";
				break;
			default:
				$content = '你输入的是:'.$this->content.'，对不起，我不太懂，我还在完善中，你可以试试问姓名、年龄、爱你...';
				break;
		}
		$this->replyData['content'] = $content;
		self::printXmlText($this->replyData);
		return;
	}

	//回复纯文本
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
	//回复图文信息
	function printXmlNews($data){
		$touser = $data['touser'];
		$fromuser = $data['fromuser'];
		$ctime = time();
		$mtype = 'news';
		$items = $data['items'];
		$count = count($items) <= 8 ? count($items) : 8;

		$template = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
		$i = 1;
		foreach ($items as $key => $v) {
			if($i > 8) break;
			$template .= "<item><Title><![CDATA[{$v['title']}]]></Title> <Description><![CDATA[{$v['desc']}]]></Description><PicUrl><![CDATA[{$v['picurl']}]]></PicUrl><Url><![CDATA[{$v['url']}]]></Url></item>";
			$i ++;
		}
		$template .= "</Articles></xml>";

		$info = sprintf($template, $touser, $fromuser, $ctime, $mtype, $count);

		echo $info;
		return;
	}
}