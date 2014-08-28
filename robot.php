<?php
/**
  * wechat php test
  */

//define your token
define("TOKEN", "pcitech");
$wechatObj = new wechatCallbackapiTest();

//for token validation: this is used while checking signature
//$wechatObj->valid();

//create menu
//$wechatObj->createMenu();

//for auto-responding
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		$listTpl=" <xml>
					 <ToUserName><![CDATA[%s]]></ToUserName>
					 <FromUserName><![CDATA[%s]]></FromUserName>
					 <CreateTime>%s</CreateTime>
					 <MsgType><![CDATA[%s]]></MsgType>
					 <ArticleCount>%s</ArticleCount>
					 <Articles>%s</Articles>
					 </xml> ";
		$itemTpl = " <item>
					 <Title><![CDATA[%s]]></Title> 
					 <Description><![CDATA[%s]]></Description>
					 <PicUrl><![CDATA[%s]]></PicUrl>
					 <Url><![CDATA[%s]]></Url>
					 </item>";		
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";   									 

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();

				//获取事件类型
				$type=$postObj->MsgType;
				if($type=='event'){
					$event = $postObj->Event;
					if($event=='subscribe'){
						$contentStr= "【新关注】欢迎关注至同思睿，我们专注于制造业，我们将为你带来最新的行业信息";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='unsubscribe'){
						$contentStr= "【取消关注】非常悲伤，你竟然不需要我了，能告诉我那些地方做得不好吗？";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='CLICK'){
						$eventKey = $postObj->EventKey;
						$openID = $postObj->FromUserName;//this is user id
						if($eventKey == "my_favorite"){
							//get article list from remote JSON
							$restURL = "http://localhost:8080/myfav";
							$baseURL = "http://124.42.107.200/myfav";
							$url = $restURL."?user=".$openID;
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							$itemCount = 0;
							$totalCount = count($array);
							for($i=0;$i<count($array);$i++){
								if($itemCount>3)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								$title = $object->title; // title is a field of your content type
								$decription = "这是收藏内容，原文已作快照。【原文地址】".$object->from;
								$picUrl = $baseURL."/".$object->image."_1.png";							
								$linkUrl =  $baseURL."/".$object->url.".html";
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}
							//if there has more items then we add a FINDMORE link
							if($itemCount>0 && totalCount > itemCount){
								$title = "查看更多收藏内容";
								$decription = "默认只显示了5条内容，这里还有更多";
								$picUrl = $baseURL."/more.png";							
								$linkUrl =  $baseURL."/more.php?user=".$openID;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;							
							}
							if($itemCount>0){
								$msgType = "news";
								$resultStr = sprintf($listTpl, $fromUsername, $toUsername, $time, $msgType,$itemCount, $itemList);
								echo $resultStr;
							}else{
								$msgType = "text";
								$contentStr= '你还没有收藏任何内容，直接发送需要收藏的链接地址就可以了哦';
								$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
								echo $resultStr;						
							}
						}else if($eventKey == "get_help"){
							$contentStr= "当前提供以下功能："
							."\n【1.添加收藏】直接发送URL即可，注意除URL外不需要编写任何其他内容。"
							."\n【2.查看收藏】点击\"我的收藏\"菜单即可查看。"
							."\n【3.内容搜索】直接发送需要搜索的关键字，如\"至同思睿\"";
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						}else{
							$contentStr= "偶滴神啊，你点错了吧？";
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						}					
					}
				}else if($type=='location'){
					$label = $postObj->Label;
					$locationX = $postObj->Location_X;
					$locationY = $postObj->Location_Y;
					$msgType = "text";
					$responseTpl="【LOC】%s 【X】%s【Y】%s";
					$contentStr = sprintf($responseTpl,$label,$locationX,$locationY);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else if($type=='text'){
					if(!empty( $keyword )){//here we return an article list
						$msgType = "news";
						$itemList = "";
						$itemCount = 0;		
						if(preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$keyword)){
							//if the user send a URL then we will scrape it and save
							$linkURL = $postObj->Url;
							$openID = $postObj->FromUserName;//this is user id
							//scrape content and save to local disk
							//exec command: phantomjs getPage.js url user dir server
							$storageDir = dirname(__FILE__)."\\myfav";
							$scriptFile = $storageDir."\\a\\getPage.js";
							$logfile = $storageDir."\\".md5($keyword).".log";
							$restServer = "http://localhost:8080/myfav";
							$command = 'D:\\Lab\\phantomjs\\phantomjs.exe '.$scriptFile." \"".$keyword."\" ".$openID." ".$storageDir." ".$restServer;
							exec($command." >".$logfile." 2>&1");
							$msgType = "text";
							$responseTpl="已收藏，即使原文被删除也可随时点击“我的收藏”查看。";
							$contentStr = sprintf($responseTpl,$linkURL);
						}else{
							//we try to search myfav
							$contentStr= '偶滴神啊，你竟然无师自通的尝试搜索与"'.$keyword.'"相关的内容。不过我们还没开放这个功能呢';
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;	
						}							
					}else{
						echo "哦~~~写点什么吧？";
					}
				}else if($type=='image'){
					$picURL = $postObj->PicUrl;
					$msgType = "text";
					$responseTpl="【Image URL】%s";
					$contentStr = sprintf($responseTpl,$picURL);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else if($type=='link'){//here we will save the content
					$eventKey = $postObj->EventKey;
					if($eventKey == ""){
						
					}else{
						$linkURL = $postObj->Url;
						$msgType = "text";
						$responseTpl="发送的内容已加入收藏，可通过“我的收藏”查看。【URL】%s";
						$contentStr = sprintf($responseTpl,$linkURL);
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}
				}else{
					echo "当前还不支持语音、图片、链接等形式哦";
				}

        }else {
        	echo "error";
        	exit;
        }
    }
		
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	private function postCreateMenu($url, $jsonData){
		$ch = curl_init($url) ;
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$result = curl_exec($ch) ;
		curl_close($ch) ;
		return $result;
	}
	
	public function createMenu(){

		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=pcitech";
		$data = '{
			"button":[
			{
			"type":"view",
			"name":"关于我们",
			"url":"http://www.pcitech.cn"
			},
			{
			"type":"click",
			"name":"我的收藏",
			"key":"my_favorite"
			},
			{
			"type":"click",
			"name":"联系方式",
			"key":"contact_info"
			}		
			]
		}';
		$this->postCreateMenu($url,$data);	
	}
	
	public function creatMenuV2(){//创建菜单
		$accessToken = $this->getAccessToken();//获取access_token
		$menuPostString = '{//构造POST给微信服务器的菜单结构体
			"button":[
			{
			"type":"view",
			"name":"关于我们",
			"url":"http://www.pcitech.cn"
			},
			{
			"type":"click",
			"name":"我的收藏",
			"key":"my_favorite"
			},
			{
			"type":"click",
			"name":"联系方式",
			"key":"contact_info"
			}		
			]
		}';
		$menuPostUrl = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$accessToken;//POST的url
		$menu = dataPost($menuPostString, $menuPostUrl);//将菜单结构体POST给微信服务器
	}	
	
	private function getAccessToken(){ //获取access_token
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".AppId."&secret=".AppSecret;
		$data = getCurl($url);//通过自定义函数getCurl得到https的内容
		$resultArr = json_decode($data, true);//转为数组
		return $resultArr["access_token"];//获取access_token
	}	
}

function getCurl($url){//get https的内容
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);//不输出内容
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$result =  curl_exec($ch);
	curl_close ($ch);
	return $result;
}
 
function dataPost($post_string, $url) {//POST方式提交数据
	$context = array ('http' => array ('method' => "POST", 'header' => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) \r\n Accept: */*", 'content' => $post_string ) );
	$stream_context = stream_context_create ( $context );
	$data = file_get_contents ( $url, FALSE, $stream_context );
	return $data;
}

function getMyFav($openID){
	$listTpl=" <xml>
				 <ToUserName><![CDATA[%s]]></ToUserName>
				 <FromUserName><![CDATA[%s]]></FromUserName>
				 <CreateTime>%s</CreateTime>
				 <MsgType><![CDATA[%s]]></MsgType>
				 <ArticleCount>%s</ArticleCount>
				 <Articles>%s</Articles>
				 </xml> ";
	$itemTpl = " <item>
				 <Title><![CDATA[%s]]></Title> 
				 <Description><![CDATA[%s]]></Description>
				 <PicUrl><![CDATA[%s]]></PicUrl>
				 <Url><![CDATA[%s]]></Url>
				 </item>";


	
}

?>