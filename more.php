<?php
/**
  * list all favorite items
  */
$listTpl=" <h2>共有%s条记录：<h2>
			<table>
			%s
			</table> ";
$itemTpl = " <tr>
			 <td><img width=160 height=120 border=0 src=\"%s\" alt=\"\"/></td> 
			 <td><a href=\"%s\"><h2>%s</h2></a></td> 
			 </tr>";
if ($_GET["user"]){
	$itemList = "";
	$openID = $_GET["user"]?$_GET["user"]:"nouser";//this is user id
	//get article list from remote JSON
	$restURL = "http://localhost:8080/myfav";
	$baseURL = "http://124.42.107.200/myfav";
	$url = $restURL."?user=".$openID;
	$lines_array = file($url);
	$lines_string = implode('',$lines_array);            
	$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
	$array = json_decode($json);
	$totalCount = count($array);
	for($i=0;$i<count($array);$i++){
		$object = $array[$i]; // The array could contain multiple instances of your content type
		$title = $object->title; // title is a field of your content type
		$picUrl = $baseURL."/".$object->image."_1.png";							
		$linkUrl =  $baseURL."/".$object->url.".html";
		$itemStr = sprintf($itemTpl,$picUrl,$linkUrl,$title);
		$itemList = $itemList.$itemStr;
	}	
	$resultStr = sprintf($listTpl, " ".$totalCount." ", $itemList);	
	echo $resultStr;
}else{
	echo "抱歉，系统好像发生了错误";
}

?>