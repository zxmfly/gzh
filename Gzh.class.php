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
	private $wxIpList = [];
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
	    		$this->access_token = self::getAccessToken();
	    		$this->wxIpList = self::getWxIp();
	 			if(!checkIp($this->wxIpList) && getIP() != '125.88.171.98') exit('IP forbid');
	    		$this->decodeMsg();
	    	}
	    }else{
	    	echo '验证失败';
	    }
	}

	private function WxApiHandle($type = ''){
		$url = 'https://api.weixin.qq.com/cgi-bin/';
		$data = [
			'appid' => $this->config['appid'],
			'secret' => $this->config['secret'],
			'grant_type' => $type,
		];
		$rs = compact('url', 'data');
		return $rs;
	}

	//access_token的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的access_token失效。
	//需要存数据库，或者文件，才比较保险
	public function getAccessToken(){
		$access_token = getCache(ACCESS_TOKEN_CKEY);

		if(empty($access_token)){
			$info = self::WxApiHandle('client_credential');
			$rs = Curl::makeRequest($info['url'].'token', $info['data']);
			$rs = json_decode($rs, 1);
			$access_token = $rs['access_token'];
			$cache_time = $rs['expires_in'] - 120;//提前2分钟过期
			setCache(ACCESS_TOKEN_CKEY, $access_token, $cache_time);
			writeLog($access_token);//记录token
		}

		return $access_token;
	}

	public function getWxIp(){
		$wxIps = getCache(WX_IP_LIST_CKEY);
		if(empty($wxIps)){
			$data['access_token'] = $this->access_token;
			$info = self::WxApiHandle();
			$rs = Curl::makeRequest($info['url'].'getcallbackip', $data);
			$d = json_decode($rs, 1);
			$wxIps = $d['ip_list'];
			foreach ($wxIps as $key => $value) {
				$start = strpos($value, '/');//存在ip地址段 "101.226.103.0/25"   
				if($start){
					$ipArr = explode('.', $value);
					$numArr = explode('/', $ipArr[3]);
					$i = $numArr[0];
					$max = $numArr[1];
					unset($ipArr[3]);
					$ip_str = implode('.', $ipArr);
					for ($i; $i<=$max; $i++) { 
						$wxIps[] = $ip_str.'.'.$i;
					}
					unset($wxIps[$key]);
				}
			}
			setCache(WX_IP_LIST_CKEY, $wxIps, 7200);
		}

		return $wxIps;
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
			if($this->event == 'subscribe'){//关注，扫码关注
				self::checkUserInfo();
				$str = "账号：".$this->replyData['touser'].", 关注";
				writeLog($str);
				$content = '你好！我是ZXM，欢迎你关注我的公众号！';
				if(isset($obj->EventKey) && strpos($obj->EventKey, 'qrscene_') !== false){//扫码关注
					$scene_id = end(explode('qrscene_', $obj->EventKey));
					$content = "欢迎您，来自场景".$scene_id."扫码关注的朋友！";
				}
				$this->replyData['content'] = $content;
				self::printXmlText($this->replyData);
			}elseif($this->event == 'unsubscribe'){
				self::checkUserInfo('unload');
				$str = "账号：".$this->replyData['touser'].", 取消关注";
				writeLog($str);
			}//二维码扫码已关注，扫码事件
			elseif($this->event == 'scan'){
				$content = $obj->EventKey > 2000 ? '永久二维码' : '临时二维码';
				$this->replyData['content'] = $content.'用户欢迎你！';
				self::printXmlText($this->replyData);
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
		if($this->content == '图文' || $this->content == 'news'){
			//图文消息个数；当用户发送文本、图片、视频、图文、地理位置这五种消息时，开发者只能回复1条图文消息；其余场景最多可回复8条图文消息 如果图文数超过限制，则将只发限制内的条数
			$items =[ 
				[
					'title' => '广州酷游',
					'desc'	=> '广州酷游官网',
					'picurl' => 'http://www.gzkuyou.com/static/img/logo.jpg',
					'url'	=> 'http://www.gzkuyou.com',
				],
			];
			$this->replyData['items'] = $items;
			self::printXmlNews($this->replyData);

			return;
		}elseif(strpos($this->content, '天气') !== false){
			self::getTianqi();

			return;
		}elseif($this->content == '二维码'){
			self::cQrcode();

			return;
		}else{
			self::checkContent();

			return;
		}

		return;
	}

	//生成推广二维码（认证公众号才能使用）
	function cQrcode(){
		$config = QrcodeConfig();
		//先获取ticket
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->access_token;
		//{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
		$data = $config;
		$rs = Curl::makeRequest($url, $data, 'json');
		$d = json_decode($rs, 1);
		if(isset($d['ticket'])){//获取临时二维码
			$ticket = $d['ticket'];
			$url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
			//返回的是一张图片，可直接使用，也可以保存
			//echo "< img src='{$url}'/>";
			$items =[ 
				[
					'title' => '扫码关注',
					'desc'	=> '收藏二维码不迷路',
					'picurl' => $url,
					'url'	=> $url,
				],
			];
			$this->replyData['items'] = $items;
			self::printXmlNews($this->replyData);

		}else{
			$this->replyData['content'] = '接口返回失败:'.$rs;
			self::printXmlText($this->replyData);
		}
	}
	//文字回复
	function checkContent(){
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
			case '百度':
				$content = "<a href='http://www.baidu.com'>百度一下</a>，你点击下试试";
				break;
			default:
				$content = 'baidu';
				break;
		}
		if($content != 'baidu'){
			$this->replyData['content'] = $content;
			self::printXmlText($this->replyData);
		}else{
			$items =[ 
				[
					'title' => $this->content,
					'desc'	=> "要查询的内容:".$this->content,
					'picurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo_top_86d58ae1.png',
					'url'	=> 'https://www.baidu.com/s?wd='.$this->content,
				],
			];
			$this->replyData['items'] = $items;
			self::printXmlNews($this->replyData);
		}

		return;
	}

	//天气查询
	function getTianqi(){
		$tqArr = GaoDe::getTianqi($this->content);
		$content = '';
		foreach ($tqArr as $key => $value) {
			if(empty($value)){
				$content .= "对不起，你输入的城市:{$key},没有查询到天气信息，请查验后再发\n--------------------------------------\n";
			}else{
				$content .= $value."\n--------------------------------------\n";
			}
		}

		$content .= '温馨提示：输入“天气”查看当前所在城市的天气，也可输入“天气:城市1:城市2:城市3"查看该城市的天气,例如“天气:广州黄埔区”，最多可同时查询3个城市的天气哟~';
		$this->replyData['content'] = $content;
		self::printXmlText($this->replyData);
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