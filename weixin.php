<?php
define(AppId, "wx481a5b1116ce11bd");//定义AppId，需要在微信公众平台申请自定义菜单后会得到
define(AppSecret, "16eca6d55b66340685f86f52018f4ce0");//定义AppSecret，需要在微信公众平台申请自定义菜单后会得到
include("wechat.class.php");//引入微信类
$wechatObj = new Wechat();//实例化微信类
$creatMenu = $wechatObj->creatMenu();//创建菜单
//for auto-responding
$wechatObj->responseMsg();
?>