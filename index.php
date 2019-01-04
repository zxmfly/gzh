<?php
include './config.php';
include './functions.php';
include './Gzh.class.php';
//首先要验证token
//1收到微信消息，解析并判断，消息类型
use gzh\GzhClass;

$gzh = new GzhClass();
