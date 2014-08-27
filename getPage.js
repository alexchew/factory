var page = require('webpage').create();
var md5 = require('./md5');
var fs = require('fs');
var url = "http://mp.weixin.qq.com/s?__biz=MzA5NjA3NzUzOA==&mid=200374388&idx=2&sn=649b284fb3bd76451ad923c96526ad66#rd";
var dir = ".";
var filename = "a";
var title = "Unknown";
var user = "unknown";

var server = "http://localhost:8090/weixin";
var headers = {"Content-Type": "application/json"};

//check mandatory parameter:dir,user,server,url
if (phantom.args.length < 2) {
    console.log('Usage: getPage.js url user [dir] [server]');
    phantom.exit();
}else if(phantom.args.length === 2){
	url = phantom.args[0];
	user = phantom.args[1];
}else if(phantom.args.length === 3){
	url = phantom.args[0];
	user = phantom.args[1];
	dir = phantom.args[2];
}else{
	url = phantom.args[0];
	user = phantom.args[1];
	dir = phantom.args[2];
	server = phantom.args[3];
}
filename = md5.MD5(url);

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
function post(){
	var data = {user:user,page:filename,url:url,title:encodeURIComponent(title)};//we get a doc with no image info
	console.log("try to post data.[data]"+JSON.stringify(data));
	page.open(server,'POST',JSON.stringify(data), headers, function (status) {
		if (status !== 'success') {
			console.log('Unable to access network. [status]'+status);
		} else {//posted
			console.log("posted."+page.plainText);
		}
		phantom.exit();
	});	
}

function render(){
	page.render(path+".png");
	console.log("screencast saved.");
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
			setTimeout(render,3000);
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
