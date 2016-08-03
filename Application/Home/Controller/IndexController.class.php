<?php
namespace Home\Controller;
use Think\Controller;

use Home\Model\WechatModel;
use Home\Model\ResponseModel;

class IndexController extends Controller {
	
    public function index() {
		define("TOKEN", "wechat");
		$wechatModel = new WechatModel();
		if (isset($_GET['echostr'])) {
			$wechatModel->valid();
		}else{
			$this->responseMsg();
		}
    }
	
	public function responseMsg()
    {
		//1.获取微信推送过来的post数据（xml格式）
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if(!empty($postStr)) {
			//2.处理消息类型，并设置回复类型和内容
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
			$msgType = $postObj->MsgType;
			$responseModel = new ResponseModel();
			switch (strtolower($msgType)) {
				case 'text':
					$content = $postObj->Content;
					$content = trim(strtolower($content));
					if(!empty($content)) {
						if(preg_match("/(time)/is", $content) || preg_match("/(时间)/is", $content)) {
							$resultStr = $responseModel->responseText($fromUsername, $toUsername, date("Y-m-d H:i:s",time()));
						} else if(preg_match("/(鲍)/is", $content) || preg_match("/(杰利)/is", $content)) {
							$newsArr = array(
									array(
										'title' => '个人网站',
										'description' => '瞎写的一些网站0.0',
										'picUrl' => 'http://pic3.nipic.com/20090622/2605630_113023052_2.jpg',
										'url' => 'http://www.baojieli.cn',
									),
									array(
										'title' => '我的博客',
										'description' => '就写了一篇博文的博客。。',
										'picUrl' => 'http://pic3.nipic.com/20090622/2605630_113023052_2.jpg',
										'url' => 'http://my.oschina.net/baojieli/blog',
									),
								);
							$resultStr = $responseModel->responseNews($fromUsername, $toUsername, $newsArr);
						} else if(preg_match("/(淘宝)/is", $content) || preg_match("/(垫)/is", $content)) {
							$newsArr = array(
									array(
										'title' => '车垫店小二',
										'description' => '主营各款汽车坐垫，大部分车型均有匹配',
										'picUrl' => 'http://img.alicdn.com/shop-logo/d6/2a/TB1EFwwHpXXXXX_aXXXwu0bFXXX.png_128x128.jpg',
										'url' => 'http://shop118529311.taobao.com',
									),
								);
							$resultStr = $responseModel->responseNews($fromUsername, $toUsername, $newsArr);
						} else if(preg_match("/(天气)/is", $content)) {
							 $resultStr = $responseModel->responseText($fromUsername, $toUsername, $this->getWeather());
						} else {
							$resultStr = $responseModel->responseText($fromUsername, $toUsername, $content);
						}
					}
					break;
				case 'event':
					//关注subscribe事件
					$event = $postObj->Event;
					if(strtolower($event) == 'subscribe') {
						$resultStr = $responseModel->responseText($fromUsername, $toUsername, "感谢您的订阅!");
					}
					if(strtolower($event) == 'click') {
						if($postObj->EventKey == 'V1001_TODAY_MUSIC') {
							$resultStr = $responseModel->responseText($fromUsername, $toUsername, "今日歌曲!");
						}
					}
					if(strtolower($event) == 'view') {
						$resultStr = $responseModel->responseText($fromUsername, $toUsername, "跳转链接是".$postObj->EventKey);
					}
					break;
				default:
					break;
			}
			echo $resultStr;
        }else{
            echo "";
            exit;
        }
    }
	
	public function getWeather() {
		$ch = curl_init();
		$url = 'http://apis.baidu.com/apistore/weatherservice/weather?citypinyin=tiantai';
		$header = array(
			'apikey: d73c8c8ff33f60d41973c1f8f370c476',
		);
		// 添加apikey到header
		curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 执行HTTP请求
		curl_setopt($ch , CURLOPT_URL , $url);
		$res = curl_exec($ch);
		$arr = json_decode($res, true);
		$content = "当前城市：".$arr['retData']['city'].
					"\n天气情况：".$arr['retData']['weather'].
					"\n气温情况：".$arr['retData']['l_tmp']."到".$arr['retData']['h_tmp']."摄氏度".
					"\n发布时间：".$arr['retData']['time'];
					
		return $content;
	}
	
	//curl采集工具
	/*function http_curl() {
		//1.初始化curl
		$ch = curl_init();
		$url = "http://www.baidu.com";
		//2.设置curl参数
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//3.采集
		$output = curl_exec($ch);
		//4.关闭
		curl_close($ch);
		var_dump($output);
	}*/
	
	/**
	* $url 接口url string
	* $type 请求类型 string
	* $res 返回数据类型 string
	* $arr post请求参数 string
	*/
	function http_curl($url, $type='get', $res='json', $arr='') {
		//1.初始化curl
		$ch = curl_init();
		//2.设置curl参数
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($type='post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
		}
		//3.采集
		$output = curl_exec($ch);
		//4.关闭
		curl_close($ch);
		if($res == 'json') {
			if(curl_errno($ch)) {
				//请求失败
				return curl_error($ch);
			} else {
				return json_decode($output, true);
			}
		}
	}
	
	function getAccessToken() {
		if($_SESSION['access_token'] && $_SESSION['expire_time']>time()) {
			var_dump($_SESSION['access_token']);
			return $_SESSION['access_token'];
		} else {
			//1.请求url地址
			//$appid = "wx060a04668f67b417";
			$appid = "wx8cb494c1fb6ea6b8";
			//$appsecret = "874f697b89886c5268c0d1120e2da72c";
			$appsecret = "1012430d45d26bf4fe8d6cb54b12acc1";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
			$res = $this->http_curl($url, 'get', 'json');
			$access_token = $res['access_token'];
			$_SESSION['access_token'] = $access_token;
			$_SESSION['expire_time'] = time()+7000;
			var_dump($access_token);
			return $access_token;
		}
	}
	
	function getServerIp() {
		$accessToken = "jtq753hFtt3hAuJ2iAuE5kc-Bex4DA6yoloK8iJ2YxTrxiar9GAoJSWMvzS7TVD9qZn333dyG8Sq_1TlL3fedhE5DIZ_A6i9PbN5ckrl7cATAVbAJAWDY";
		$url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$accessToken;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		curl_close($ch);
		if(curl_errno($ch)) {
			var_dump(curl_errno($ch));
		}
		$arr = json_decode($res, true);
		echo "<pre>";
		var_dump($arr);
		echo "</pre>";
	}
	
	public function defineMenu() {
		//目前微信接口的调用方式都是通过curl get/post
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		$postArr = array(
			'buttton' => array(
				//一级菜单
				array(
					'name' => urlencode('菜单1'),
					'type' => 'click',
					'key' => 'item1',
				),
				array(
					'name' => urlencode('菜单2'),
					'sub_button' => array(
						array(
							'name' => urlencode('歌曲'),
							'type' => 'click',
							'key' => 'songs',
						),
						array(
							'name' => urlencode('电影'),
							'type' => 'view',
							'url' => 'www.baidu.com',
						),
					),
				),
			),
		);
		$postJson = urldecode(json_encode($postArr));
		
		$postJson = ' {
     "button":[
     {	
          "type":"click",
          "name":"今日歌曲",
          "key":"V1001_TODAY_MUSIC"
      },
      {
           "name":"菜单",
           "sub_button":[
           {	
               "type":"view",
               "name":"搜索",
               "url":"http://www.soso.com/"
            },
            {
               "type":"view",
               "name":"视频",
               "url":"http://v.qq.com/"
            },
            {
               "type":"click",
               "name":"赞一下我们",
               "key":"V1001_GOOD"
            }]
       }]
 }';

		echo "<hr/>";
		var_dump($postJson);
		echo "<hr/>";
		$res = $this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
	}
	
	function sendMsgAll() {
		//1.获取全局access_token
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".$access_token;
		//2.组装群发接口数据 json
		$arr = array(
			'touser' => 'oE_zlv2n8sfdLSPIg0v28coOsUFs',
			'text' => array(
				'content' => urlencode('群发')
			),
			'msgtype' => 'text'
		);
		$postJson = urldecode(json_encode($arr));
		//3.调用curl
		$res = $this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
	}
	
	function sendTemplateMsg() {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
		$arr = array(
			'touser' => 'oE_zlv2n8sfdLSPIg0v28coOsUFs',
			'template_id' => 'uYjPlpmaI2ZZy9IMfbZMuAUSAJJZ4975zCOzQSIXQbo',
			'url' => 'http://www.baojieli.cn',
			'data' => array(
				'name' => array('value'=>'hello', 'color'=>'#173177'),
				'money' => array('value'=>'100', 'color'=>'#173177'),
				'date' => array('value'=>date('Y-m-d H:i:s'), 'color'=>'#173177'),
			),
		);
		$postJson = urldecode(json_encode($arr));
		$res = $this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
	}
	
	//拉取用户openId
	function getBaseInfo() {
		//1.获取到code
		$appid = "wx8cb494c1fb6ea6b8";
		$redirect_uri = urlencode("http://www.baojieli.cn/Wechat/index.php/Home/Index/getUserOpenId");
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snapi_base&state=123#wechat_redirect";
		header('location:'.$url);
	}
	function getUserOpenId() {
		//2.获取网页授权的access_token
		//3.拉取用户openId
		$appid = "wx8cb494c1fb6ea6b8";
		$appsecret = "1012430d45d26bf4fe8d6cb54b12acc1";
		$code = $_GET['code'];
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
		$res = $this->http_curl($url, 'get');
		var_dump($res);
		$openid = $res['openid'];
		//页面 index.tpl
		//$this->display('index.tpl');
	}
	
	function getUserDetail() {
		$appid = "wx8cb494c1fb6ea6b8";
		$redirect_uri = urlencode("http://www.baojieli.cn/Wechat/index.php/Home/Index/getUserInfo");
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snapi_userinfo&state=123#wechat_redirect";
		header('location:'.$url);
	}
	function getUserInfo() {
		$appid = "wx8cb494c1fb6ea6b8";
		$appsecret = "1012430d45d26bf4fe8d6cb54b12acc1";
		$code = $_GET['code'];
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
		$res = $this->http_curl($url, 'get');
		$access_token = $res['access_token'];
		$openid = $res['openid'];
		//3.拉取用户详细信息
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN ";
		$res = $this->http_curl($url);
		var_dump($res);
	}
	//微信分享
	function share() {
		//1.获取jsapi_ticket票据
		$jsapi_ticket = $this->getJsApiTicket();
		
		$timestamp = time();
		$noncestr = $this->getRandomCode();
		$url = "http://www.baojieli.cn/Wechat/index.php/Home/Index/share";
		//2.获取signature
		$tmpArr = array(
			'jsapi_ticket' => $jsapi_ticket,
			'timestamp' => $timestamp,
			'noncestr' => $noncestr,
			'url' => $url
		);
		//字典序排序
        ksort($tmpArr, SORT_STRING);
		foreach($tmpArr as $k=>$v) {
			//&times 会被解析成 乘号。。。
			$signature .= $k. "=" . $v . "&";
		}
		$signature = substr($signature, 0, strlen($signature)-1);
		echo $signature;echo "<hr>";
		
		$signature = sha1($signature);
		echo $signature;
		
		$this->assign('timestamp', $timestamp);
		$this->assign('nonceStr', $noncestr);
		$this->assign('signature', $signature);
		$this->display('share');
	}
	//获取jsapi_ticket票据
	function getJsApiTicket() {
		if($_SESSION['jsapi_ticket'] && $_SESSION['jsapi_ticket_expire_time']>time()) {
			$jsapi_ticket = $_SESSION['jsapi_ticket'];
		} else {
			$access_token = $this->getAccessToken();
			$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=wx_card";
			$res = $this->http_curl($url);
			$jsapi_ticket = $res['ticket'];
			$_SESSION['jsapi_ticket'] = $jsapi_ticket;
			$_SESSION['jsapi_ticket_expire_time'] = time()+7000;
		}
		return $jsapi_ticket;
	}
	function getRandomCode($num=16) {
		$arr = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
			'0','1','2','3','4','5','6','7','8','9');
		$tmpstr = '';
		$max = count($arr);
		for($i=1; $i<=$num; $i++) {
			$tmpstr .= $arr[rand(0, $max-1)];
		}
		return $tmpstr;
	}
	
	
	//以下为二维码生成
	function getQrCode() {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
		$postArr = array(
			'expire_seconds' => 604800, //24*60*60*7
			'action_name' => 'QR_SCENE',
			'action_info' => array(
				'scene' => array('scene_id' => 2000),
			),
		);
		$postJson = json_encode($postArr);
		$res = $this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
		$ticket = $res['ticket'];
		//2.使用ticket获取二维码图片
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
		echo "<img src=".$url."/>";
	}
}