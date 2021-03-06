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
		$description = "";

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
						$contentStr= "【欢迎】感谢关注至同思睿，我们专注于制造业信息化，我们将为你带来最新的行业信息";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='unsubscribe'){
						$contentStr= "【取消关注】非常悲伤，你竟然不需要我了，能告诉我那些地方做得不好吗？";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='CLICK'||$event=='click'){
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
						}else if($eventKey == "integration"){
							$contentStr= "企业集成能力是我们产品的核心功能，包括："
							."\n【1.数据集成】从异构数据源获取、转换、加载数据到数据仓库，并提供统一的REST服了数据服务接口"
							."\n【2.应用集成】通过集成遗留IT系统的接口，能够在原有IT系统内提供新的功能，或者在新的系统内集成访问第三方系统功能"
							."\n【3.企业搜索】对企业所有类型内容包括文档及数据记录进行索引，并提供企业搜索服务，该服务可直接嵌入其他应用系统";
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						}else if($eventKey == "mds"){
							$contentStr= "对于制造业，一端要面临激烈的市场竞争，一端要严格控制生产过程中所需的各种原材料，为保持竞争优势，必须拥有全面的材料信息管理系统。先择MDS有以下主要特征："
							."\n【1.完备的企业级材料主数据库】可直接对接IMDS或CAMDS，供应商也可直接填报数据，总部及分公司可直接从材料库查阅材料信息"
							."\n【2.与BOM集成】直接对接BOM系统，建立完整的从产品到材料到物质数据链条，可轻松应对各类环保合规检查"
							."\n【3.可扩展的法规库】可与现有法规库实现集成，便于法规查阅。也可集成云端数字化法规库，实现自动化合规分析"
							."\n【4.相似材料推荐】得益于强大的搜索分析功能，设计工程师可通过对照材料找相似材料";
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						}else if($eventKey == "vr"){
							$contentStr= "我们拥有专业的虚拟现实设计及制作团队，在航空、机车、汽车等多个领域拥有众多成功案例";
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
							$contentStr="已收藏，即使原文被删除也可随时点击“我的收藏”查看。";
							//$contentStr = sprintf($responseTpl,$linkURL);
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;	
						}else{//we try to search myfav
							$openID = $postObj->FromUserName;//this is user id
							//get article list from remote JSON
							$restURL = "http://124.42.107.200:8090/solr/select?wt=json&fl=title,uri,thumbnailURL&q=";
							$baseURL = "http://124.42.107.200/myfav";
							//source:weixin AND author:alexchew  AND (title:2014 OR id:3f8aa03f821704649d73222d59e453fb)	
							$q = "author:".$openID." AND (title:".$keyword." OR content:".$keyword.")";
							$url = $restURL.urlencode($q);//NOTICE here: we must encode query string
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);                 
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							if(empty($json)){
								echo $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, "text", "搜索服务当前不可用");	
							}else{
								$obj = json_decode($json);
								$itemCount = 0;
								$array = $obj->response->docs;
								for($i=0;$i<count($array);$i++){
									if($itemCount>4)//we only display 4 items for mobile
										break;
									$object = $array[$i]; // The array could contain multiple instances of your content type
									$title = $object->title; // title is a field of your content type
									$decription = "这是收藏内容，原文已作快照。【原文地址】".$object->from;
									$picUrl = $object->thumbnailURL;							
									$linkUrl = $object->uri;
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
									$contentStr= '在你的收藏里没找到任何符合"'.$keyword.'"的内容，重新输入看看？';
									$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
									echo $resultStr;						
								}				
							}
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
			"button": [
				{
					"type": "view", 
					"name": "公司网站", 
					"url": "http://www.pcitech.cn"
				}, 
				{
					"name": "产品服务", 
					"sub_button": [
						{
							"type": "click", 
							"name": "材料数据库", 
							"key": "mds"
						}, 
						{
							"type": "click", 
							"name": "企业集成", 
							"key": "integration"
						}, 
						{
							"type": "click", 
							"name": "虚拟现实", 
							"key": "vr"
						}
					]
				}, 
				{
					"name": "我的...", 
					"sub_button": [
						{
							"type": "click", 
							"name": "我的收藏", 
							"key": "my_favorite"
						}, 
						{
							"type": "click", 
							"name": "使用帮助", 
							"key": "get_help"
						}
					]
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

function curl_file_get_contents($durl){  
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $durl);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回    
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回    
    $r = curl_exec($ch);  
    curl_close($ch);  
    return $r;  
}
 
function dataPost($post_string, $url) {//POST方式提交数据
	$context = array ('http' => array ('method' => "POST", 'header' => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) \r\n Accept: */*", 'content' => $post_string ) );
	$stream_context = stream_context_create ( $context );
	$data = file_get_contents ( $url, FALSE, $stream_context );
	return $data;
}

?>