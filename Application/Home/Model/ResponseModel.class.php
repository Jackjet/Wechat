<?php
namespace Home\Model;
class ResponseModel {
	//回复图文（多）
	public function responseNews($toUsername, $fromUsername, $newsArr) {
		$newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>".count($newsArr)."</ArticleCount>
					<Articles>";
		foreach($newsArr as $k=>$v) {
			$newsTpl .= "<item>
						<Title><![CDATA[".$v['title']."]]></Title>
						<Description><![CDATA[".$v['description']."]]></Description>
						<PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
						<Url><![CDATA[".$v['url']."]]></Url>
						</item>";
		}
		$newsTpl .= "</Articles></xml>";
		return sprintf($newsTpl, $toUsername, $fromUsername, time(), 'news');
	}
	//回复文本
	public function responseText($toUsername, $fromUsername, $content) {
		return sprintf(C('textTpl'), $toUsername, $fromUsername, time(), $content);
	}
	
}