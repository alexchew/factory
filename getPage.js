var page = require('webpage').create();
var md5 = require('./md5');
var fs = require('fs');
var url = "http://mp.weixin.qq.com/s?__biz=MzA5NjA3NzUzOA==&mid=200374388&idx=2&sn=649b284fb3bd76451ad923c96526ad66#rd";
var dir = ".";
var filename = "a";
var title = "Unknown";
var user = "unknown";

//var solrServer = "http://124.42.107.200:8090/solr/update/json";
var solrServer = "http://localhost:8080/index";

var server = "http://localhost:8090/weixin";
var headers = {"Content-Type": "application/json"};

//check mandatory parameter:dir,user,server,url
if (phantom.args.length < 2) {
    console.log('Usage: getPage.js url user [dir] [server] [filename]');
    phantom.exit();
}else if(phantom.args.length === 2){
	url = phantom.args[0];
	user = phantom.args[1];
	filename = md5.MD5(url);	
}else if(phantom.args.length === 3){
	url = phantom.args[0];
	user = phantom.args[1];
	dir = phantom.args[2];	
	filename = md5.MD5(url);
}else if(phantom.args.length === 4){
	url = phantom.args[0];
	user = phantom.args[1];
	dir = phantom.args[2];
	server = phantom.args[3];
	filename = md5.MD5(url);
}else{
	url = phantom.args[0];
	user = phantom.args[1];
	dir = phantom.args[2];
	server = phantom.args[3];
	filename = phantom.args[4];
}

var path = dir+"/"+filename;

//prepare agent and proxy
//console.log('The default user agent is ' + page.settings.userAgent);
//page.settings.userAgent = 'SpecialAgent';

//screencast
/*
page.onLoadFinished = function(status) {
  var currentURL = page.url;
  console.log("Status:  " + status);
  console.log("Loaded:  " + currentURL);
  if(status=='success'){
	  targetImageURL = md5.MD5(currentURL);
	  //page.clipRect={top:200,left:40,width:260,height:200};
	  page.render("a1.png");
	  console.log("screencast saved.");
  }else{
	  console.log("Load error! cannot access network.");
  }
  //setTimeout(postImageInfo,100);
      //phantom.exit();
}
//**/

function commitIndex(){
	console.log("try to commit index.[url]"+solrServer);
	page.open(solrServer+"?commit=true", function (status) {
		if (status !== 'success') {
			console.log('Unable to access network');
		} else {
			console.log('Index committed.[response]'+page.plainText);
		}
		phantom.exit();
	});
}

//post the document to Solr
function postIndex(){
	var favid = md5.MD5(url+user);
	var content = "Cannot read data from source page"; 
	try{
		content = fs.read(path+".html");
	}catch(err){
		console.log("error while reading file.[file]"+path+".html\n[error]"+err);
	}
	title = encodeURIComponent(title);
	content = encodeURIComponent(content);
	var data =//[  //notice here: for submitting to solr we should use array
		{
			id:favid,
			//title:"[original]"+title+"[GBK2UTF8]"+encodeUtil.GB2312ToUTF8(title)+"[UTF82GBK]"+encodeUtil.UTF8ToGB2312(title),
			title:title,
			fileName: filename+".html",
			author: user,
			format: "htm",
			content:content,
			category: "myfav",
			classifier: "com.prophet.channel.weixin.myfav",
			uri: "http://124.42.107.200/myfav/"+filename+".html",
			source: "nodesolr",
			securityLevel: 0,
			summary: "content from weixin myfav",
			thumbnailURL: "http://124.42.107.200/myfav/"+filename+"_1.png"
		};
	//];
	//console.log("try to post index.[data]"+JSON.stringify(data));
	console.log("try to post index.[data]"+data/*[0]*/.title);
	page.open(solrServer,'POST',JSON.stringify(data), headers, function (status) {
		if (status !== 'success') {
			console.log('Unable to access network. [status]'+status);
			phantom.exit();
		} else {//posted
			console.log("posted."+page.plainText);
			setTimeout(commitIndex,100);
		}
	});	
}

function post(){
	var favid = md5.MD5(url+user);
	var data = {favid:favid,user:user,url:filename,from:url,title:encodeURIComponent(title),image:filename};//we get a doc with no image info
	console.log("try to post data.[data]"+JSON.stringify(data));
	page.open(server,'POST',JSON.stringify(data), headers, function (status) {
		if (status !== 'success') {
			console.log('Unable to access network. [status]'+status);
			phantom.exit();			
		} else {//posted
			console.log("posted."+page.plainText);
			setTimeout(postIndex,100);
		}
	});	
}

function render(){
	page.render(path+".png");
	console.log("screencast saved.");
	page.clipRect={top:30,left:15,width:360,height:270};
	page.render(path+"_1.png");
	console.log("small logo saved.");	
	setTimeout(post,100);
}

//get content and save to local disk
function open(){
	console.log("try to open page.[url]"+url);
	page.open(url, function (status) {
		if (status !== 'success') {
			console.log('Unable to access network');
		} else {
			title = page.title;
			fs.write(path+".html",page.content,'w');
			console.log("page saved.");
			setTimeout(render,100);
		}
	});
}

function get_page(){
	if (!fs.exists(path+".html")) {
		console.log("the page is new. [url]"+url);
		open();//save and post page info
	}else{
		console.log("the page has been saved. [url]"+url);
		post();//just post page info
	}
}

//get_page();
open();
