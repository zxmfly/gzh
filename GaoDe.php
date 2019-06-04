<?php 
namespace gzh;

class GaoDe{

	function getTianqi($content){
		if($content == '天气'){
			$adcodeArr = GaoDe::getCodeByip();

		}else{
			$content = trim( str_replace("\r\n", ':', $content) );
			$content = str_replace(" ", ':', $content);
			$content = str_replace("|", ':', $content);
			$content = str_replace("，", ':', $content);
			$content = str_replace(",", ':', $content);
			$content = str_replace("+", ':', $content);
			$content = str_replace("_", ':', $content);
			$content = str_replace("：", ':', $content);

			$tqArr = explode(':', $content);
			if(count($tqArr) < 2){
				$adcodeArr = self::getCodeByip();
			}else{
				foreach ($tqArr as $key => $value) {
					if($key == 0) continue;
					if($key > 3) break;
					$adcodeArr[$value] = self::getCodeByName($value);
				}
			}
		}

		$result = [];
		if($adcodeArr){
			foreach ($adcodeArr as $key => $value) {
				$result[$key] = empty($value) ? [] : self::getWeather($value);
			}
		}

		return $result;
	}
	//实时天气
	function formatTianqiBase($arr){
		$str = "城市：{$arr['city']} \n".
				"天气：{$arr['weather']} \n".
				"温度：{$arr['temperature']} \n".
				"风向：{$arr['winddirection']} \n".
				"风级：{$arr['windpower']} \n".
				"湿度：{$arr['humidity']} \n".
				"时间：{$arr['reporttime']}";
		return $str;
	}
	//预报天气
	function formatTianqiAll($arr){
		$str = "日期：{$arr['date']} \n".
				"星期：".getWeek($arr['week'], 0)." \n".
				"天气：day {$arr['dayweather']} ~ night {$arr['nightweather']} \n".
				"温度：day {$arr['daytemp']} ~ night {$arr['nighttemp']} \n".
				"风向：day {$arr['daywind']} ~ night {$arr['nightwind']} \n".
				"风级：day {$arr['daypower']} ~ night {$arr['nightpower']}\n";
		return $str;
	}

	function getWeather($adcode){
		$info = getCache('weather_'.$adcode);
		if($info) return $info;
		$ext = 'all';
		$info = self::handle('weather/weatherInfo');
		$data = $info['data'];
		$data['city'] = $adcode;
		$data['extensions'] = $ext;//默认base,只有当前天气
		$re = Curl::makeRequest($info['url'], $data);
		$rs = json_decode($re, 1);
		if($rs['status'] == 1){
			if($ext == 'base'){
				$info = self::formatTianqiBase($rs['lives'][0]);//实时天气
			}else{
				$info = '';
				foreach ($rs['forecasts'][0] as $key => $value) {
					if($key == 'city'){
						$info = "城市：{$value} \n ----------\n";
					}
					elseif($key == 'casts'){
						$c = count($value);
						foreach ($value as $k => $v) {
							$info .= self::formatTianqiAll($v);
							if($k < $c -1) $info .= "-------------------------------------- \n ";
						}		
					}else{
						continue;
					}
				}
			}
			setCache('weather_'.$adcode, $info, 3600*4);
			return $info;
		}

		return [];
	}

	private function handle($type = ''){
		$url = 'https://restapi.amap.com/v3/'.$type;
		$config = gaoDeConfig();
		$data = [
			'output' => $config['output'],
			'key' => $config['key'],
		];
		if($type == 'ip'){
			$data['ip'] = '125.88.171.98';//getIP();
		}

		return compact('url', 'data');

	}

	static public function getCodeByName($city)
	{	$adcode = getCache('adcode_'.$city);
		if($adcode) return $adcode;

		$info = self::handle('place/text');
		$data = $info['data'];
		$data['keywords'] = $city;
		$data['city'] = $city;
		$data['extensions'] = 'all';
		$re = Curl::makeRequest($info['url'], $data);
		$rs = json_decode($re, 1);
		if($rs['status'] == 1){
			$adcode = $rs['pois'][0]['adcode'];
			setCache('adcode_'.$city, $adcode, 86400);
			return $adcode;
		}

		return 0;
	}

	static public function getCodeByip(){
		$info = self::handle('ip');

		$ip = $info['data']['ip'];
		$adcodeArr = getCache('adcode_'.$ip);
		if($adcodeArr) return $adcodeArr;
		$adcodeArr = [];
		$re = Curl::makeRequest($info['url'], $info['data']);
		$rs = json_decode($re, 1);
		if($rs['status'] == 1){
			$adcodeArr = [ $rs['city']=> $rs['adcode'] ];
			setCache('adcode_'.$rs['city'], $adcodeArr, 86400);
			setCache('adcode_'.$ip, $adcodeArr, 86400);
		}
		
		return $adcodeArr;
	}

}