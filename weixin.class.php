<?php
class Wechat{
	private function getAccessToken(){ //获取access_token
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".AppId."&secret=".AppSecret;
		$data = getCurl($url);//通过自定义函数getCurl得到https的内容
		$resultArr = json_decode($data, true);//转为数组
		return $resultArr["access_token"];//获取access_token
	}
	 
	public function responseMsg(){
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		//extract post data
		if (!empty($postStr)){
				$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
				$fromUsername = $postObj->FromUserName;
				$toUsername = $postObj->ToUserName;
				$keyword = trim($postObj->Content);
				$time = time();
				$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";   

				//获取事件类型
				$type=$postObj->MsgType;
				if($type=='event'){
					$event = $postObj->Event;
					if($event=='subscribe'){
						$contentStr= "【新关注】欢迎关注至同思睿，我们专注于制造业信息化，将向您提供最新的行业信息";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='unsubscribe'){
						$contentStr= "【取消关注】非常悲伤，你竟然不需要我了，能告诉我那些地方做得不好吗？";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='CLICK'){
						$contentStr= "【点击】点击自定义菜单";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
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
						$itemList = "";
						$itemCount = 0;		
						if($keyword=='favorite' || $keyword=='收藏'){
							//get coupon list from remote JSON
							$url = "http://124.42.107.200:8080/foodinfos";
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							for($i=0;$i<count($array);$i++){
								if($itemCount>4)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								if(count($object->food)>0){
									$title = "【".implode(' ',$object->food)."】".$object->title.' '.$object->time; 
								}elseif(count($object->tag)>0){
									$title = "【".implode(' ',$object->tag)."】".$object->title.' '.$object->time; 
								}else{
									$title = $object->title.' '.$object->time; 
								}
								$decription = $object->time.' '.$object->title;
								$picUrl = $object->image;//"http://www.zhuqingchun.com/kill.jpg";
								$linkUrl = $object->url;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}						
						}else{
							//we will search content match keywords
						}
						if($itemCount>0){
							$resultStr = sprintf($listTpl, $fromUsername, $toUsername, $time, $msgType,$itemCount, $itemList);
							echo $resultStr;
						}else{
							$contentStr= '调试中，还没找到与"'.$keyword.'"相关的内容。当前仅支持收藏内容浏览';
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
				}else if($type=='link'){
					$linkURL = $postObj->Url;
					$msgType = "text";
					$responseTpl="【Link URL】%s";
					$contentStr = sprintf($responseTpl,$linkURL);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else{
					echo "当前还不支持语音、图片、链接等形式哦";
				}

		}else {
			echo "error";
			exit;
		}
	} 
	 
	public function creatMenu(){//创建菜单
		$accessToken = $this->getAccessToken();//获取access_token
		$menuPostString = '{//构造POST给微信服务器的菜单结构体
			"button":[
			{
			"type":"view",
			"name":"关于我们",
			"url":"http://www.pcitech.cn"
			},
			{
			"type":"view",
			"name":"我的收藏",
			"url":"http://www.baidu.com"
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
 ?>