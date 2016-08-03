<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
	<head>
		<title>微信分享测试</title>
		<meta name='viewpoint' content='initial-scale=1.0;width=device-width' />
		<meta http-equiv='content' content='text/html;charset=utf-8' />
		<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
	<head>
	<body>
		<script>
			wx.config({
				debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: 'wx8cb494c1fb6ea6b8', // 必填，公众号的唯一标识
				timestamp: '<<?php echo ($timestamp); ?>>', // 必填，生成签名的时间戳
				nonceStr: '<<?php echo ($nonceStr); ?>>', // 必填，生成签名的随机串
				signature: '<<?php echo ($signature); ?>>',// 必填，签名，见附录1
				jsApiList: [
					'onMenuShareTimeline',
					'onMenuShareAppMessage',
					'chooseImage'
				] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
			});
			wx.ready(function(){
				wx.onMenuShareTimeline({
					title: 'title', // 分享标题
					link: 'http://www.baojieli.cn', // 分享链接
					imgUrl: 'http://pic3.nipic.com/20090622/2605630_113023052_2.jpg', // 分享图标
					success: function () { 
						// 用户确认分享后执行的回调函数
					},
					cancel: function () { 
						// 用户取消分享后执行的回调函数
					}
				});
			});
			wx.error(function(res){
				wx.onMenuShareAppMessage({
					title: 'title', // 分享标题
					desc: '分享测试', // 分享描述
					link: 'http://www.baojieli.cn', // 分享链接
					imgUrl: 'http://pic3.nipic.com/20090622/2605630_113023052_2.jpg', // 分享图标
					type: 'link', // 分享类型,music、video或link，不填默认为link
					dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
					success: function () { 
						// 用户确认分享后执行的回调函数
					},
					cancel: function () { 
						// 用户取消分享后执行的回调函数
					}
				});
			});
			function show() {
				wx.chooseImage({
					count: 1, // 默认9
					sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
					success: function (res) {
						var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
					}
				});
			}
		</script>
		<button onclick="show()">相册</button>
		<?php echo ($nonceStr); ?><hr><?php echo ($signature); ?>
	</body>
</html>