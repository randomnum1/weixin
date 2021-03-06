<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>JSSDK</title>
  <link rel="stylesheet" type="text/css" href="/apily/Public/Home/css/style.css" />
</head>
<body>


</body>

</html>

<script src="/apily/Public/Home/js/jquery.min.js"></script>
<script src="/apily/Public/Home/js/flexible.js"></script>
<script src="http://res.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
<script type="text/javascript">
  /*
   * 注意：
   * 1. 所有的JS接口只能在公众号绑定的域名下调用，公众号开发者需要先登录微信公众平台进入“公众号设置”的“功能设置”里填写“JS接口安全域名”。
   * 2. 如果发现在 Android 不能分享自定义内容，请到官网下载最新的包覆盖安装，Android 自定义分享接口需升级至 6.0.2.58 版本及以上。
   * 3. 常见问题及完整 JS-SDK 文档地址：http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html
   *
   * 开发中遇到问题详见文档“附录5-常见错误及解决办法”解决，如仍未能解决可通过以下渠道反馈：
   * 邮箱地址：weixin-open@qq.com
   * 邮件主题：【微信JS-SDK反馈】具体问题
   * 邮件内容说明：用简明的语言描述问题所在，并交代清楚遇到该问题的场景，可附上截屏图片，微信团队会尽快处理你的反馈。
   */
	wx.config({
		debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
		appId: "<?php echo ($appId); ?>", 				// 必填，公众号的唯一标识
		timestamp: "<?php echo ($timestamp); ?>", 		// 必填，生成签名的时间戳
		nonceStr: "<?php echo ($nonceStr); ?>", 		// 必填，生成签名的随机串
		signature: "<?php echo ($signature); ?>",		// 必填，签名
		jsApiList: [
			'scanQRCode'
		]
	});


	wx.ready(function () {

		wx.scanQRCode({
			needResult: 1,
			desc: 'scanQRCode desc',
			success: function (res) {

				window.location.href = res.resultStr;
			}
		});

		
	});

	wx.error(function (res) {
		alert(res.errMsg);
	});


</script>