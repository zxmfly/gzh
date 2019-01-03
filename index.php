<?php
include './config.php';
include './Gzh.class.php';
//首先要验证token
//1收到微信消息，解析并判断，消息类型

$gzh = new Gzh();

//验证token(首次就好)
$gzh->checkToken();

//解析消息