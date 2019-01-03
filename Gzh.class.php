<?php

class Gzh
{	
	const $token = '';
	function __construct()
	{
		$this->token = TOKEN;
	}








	//验证token
	function checkToken(){
	    $tmpArr = [$this->token, $_GET['timestamp'], $_GET['nonce']];
	    sort($tmpArr, SORT_STRING);
	    $tmpStr = sha1( implode( $tmpArr ) );

	    if( $_GET['signature']  ==  $tmpStr){
	        echo $_GET['echostr'];
	        exit;
	    }else{
	    	echo $_GET['验证失败'];
	        return false;
	    }
	}

}