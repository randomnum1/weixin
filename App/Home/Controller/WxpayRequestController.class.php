<?php
namespace Home\Controller;
use Think\Controller;

class WxpayRequestController extends WxpayCommonController {

	public function __construct(){
        parent::__construct();
        $this->api = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->notify_url = 'http://miyacloud.cn/apily/index.php/WxpayRequest/notify_url';   
    }

    public function index(){
    	session('WxpayRecordId','5');
    	
    	$this->display();  
    }

    public function _before_apily(){
    	//获取openid
    	if(!isset($_GET['code'])){
    		$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    		$jumpurl = $this->oauth2_authorize($redirect_url,'snsapi_userinfo','123');
    		Header("Location: $jumpurl");
    	}else{
    		$access_token_oauth2 = $this->oauth2_access_token($_GET['code']);
    		$openid = $access_token_oauth2['openid'];
    		session('openid',$openid);
    	}
    }

    public function apily(){
    	$project = D('Record')->field('re_pr_name,re_order_num,re_order_fee')->where(array('re_id'=>$_SESSION['WxpayRecordId'],'re_order_state'=>'1'))->find();
    	if($project){
    		$this->assign('project_name',$project['re_pr_name']);
			$this->assign('project_fee',$project['re_order_fee']);

	    	$parameters["appid"] = $this->appid;
			$parameters["mch_id"] = $this->mechid;
			$parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];
			$parameters["nonce_str"] = $this->createNoncestr();
			$parameters["body"] = '小琢骨健康-'.$project['re_pr_name'];
			$parameters["out_trade_no"] = $project['re_order_num'];
			$parameters["total_fee"] = $project['re_order_fee']*100;
			$parameters["notify_url"] = $this->notify_url;
			$parameters["trade_type"] = 'JSAPI';
			$parameters["openid"] = $_SESSION['openid'];
			$parameters["sign"] = $this->getSign($parameters);
	    	$xml = $this->arrayToXml($parameters);
			$res = $this->postXmlCurl($xml,$this->api,30);
			$res = $this->xmlToArray($res);
			//失败原因：订单已支付、支付参数不正确
			// var_dump($res);
			// die();
			if( ($res['return_code'] == 'SUCCESS') && ($res['result_code'] == 'SUCCESS') ){
				$prepay_id = $res['prepay_id'];
				$jsApiParameters = $this->jsApiObj($prepay_id);
				$this->assign('state','success');
				$this->assign('jsApiParameters',$jsApiParameters);
			}else{
				$this->assign('state','fail');
				$jsApiParameters = '{"appid":""}';
				$this->assign('jsApiParameters',$jsApiParameters);
			}
    	}else{
			$this->assign('state','fail');
			$jsApiParameters = '{"appid":""}';
			$this->assign('jsApiParameters',$jsApiParameters);
    	}	
		$this->display();
    }


    public function jsApiObj($prepay_id){
		$jsApiObj["appId"] = $this->appid;
		$jsApiObj["timeStamp"] = time();
		$jsApiObj["nonceStr"] = $this->createNoncestr();
		$jsApiObj["package"] = 'prepay_id='.$prepay_id;
		$jsApiObj["signType"] = 'MD5';
		$jsApiObj["paySign"] = $this->getSign($jsApiObj);
		$jsApiParameters = json_encode($jsApiObj);
		return $jsApiParameters;
    }


    public function notify_url(){
    	$order = file_get_contents('php://input');
    	$response = $this->xmlToArray($order);
    	if( ($response['return_code'] == 'SUCCESS') && ($response['result_code'] == 'SUCCESS') ){
			$where['re_order_num'] = $response['out_trade_no'];
			$order_info = D('record')->where($where)->find();
			if($order_info['re_order_state'] == 0){
		    	//更改订单状态
				$str = $response['time_end'];
		        $temp = substr($str,0,4).'-'.substr($str,4,2).'-'.substr($str,6,2).' '.substr($str,8,2).':'.substr($str,10,2).':'.substr($str,12,2);
		        $temp = strtotime($temp);
				$data['re_order_time'] = $temp;

				$data['re_order_type'] = $response['trade_type'];
				$data['re_order_state'] = 1;
				D('record')->where($where)->save($data);
    			//日志
				$history_order = file_get_contents('./log.txt');
				$history_order = $history_order."\r\n-\r\n".$order;
    			file_put_contents('./log.txt', $history_order);
			}
    	}
    }

    public function send_message($tel,$code){
        $url ="https://sh2.ipyy.com/sms.aspx";
        $mobile = $tel;
        $content = "'【小琢预约提示】预约人：@，@，预约时间：@，联系方式：@';";
        $body=array(
            'action'=>'send',
            'userid'=>'',
            'account'=>'jksc1069',
            'password'=>'a1234566',
            'mobile'=>$mobile,
            'extno'=>'',
            'content'=>$content,
            'sendtime'=>''
        );
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        curl_close($ch);
    }


    public function state(){
    	$project = D('Record')->field('re_pr_name,re_order_state')->where(array('re_id'=>$_SESSION['WxpayRecordId']))->find();
    	if($project['re_order_state']==1){
	    	$json['data'] = '';
	    	$json['result']['type'] = 'success';
	    	$json['result']['reason'] = 'ok';
    	}else{
    		$json['data'] = 'null';
	    	$json['result']['type'] = 'fail';
	    	$json['result']['reason'] = 'error';
    	}
    	$this->ajaxReturn($json);
    }	


    public function create_code_url($id){
		$parameters["appid"] = $this->appid;
		$parameters["mch_id"] = $this->mechid;
		$parameters["nonce_str"] = $this->createNoncestr();
		$parameters['product_id'] = $id;
		$parameters["time_stamp"] = time();
		$parameters['sign'] = $this->getSign($parameters);
	    $url = "weixin://wxpay/bizpayurl?appid=".$parameters["appid"]."%26mch_id=".$parameters["mch_id"]."%26nonce_str=".$parameters["nonce_str"]."%26product_id=".$parameters['product_id']."%26time_stamp=".$parameters["time_stamp"]."%26sign=".$parameters["sign"];
	    return $url;
    }


    public function qr_code(){
    	$project = D('Record')->field('re_pr_name,re_order_fee')->where(array('re_id'=>$_SESSION['WxpayRecordId'],'re_order_state'=>'1'))->find();
    	if($project){
			$weixinurl = $this->create_code_url($_SESSION['WxpayRecordId']);

			$this->assign('project_name',$project['re_pr_name']);
			$this->assign('project_fee',$project['re_order_fee']);   
			$this->assign('project_code',$weixinurl);

			$this->display();
    	}else{
    		echo "<script>alert('系统错误');history.back();</script>";
    	}
    }


    public function code(){
    	$content = file_get_contents("php://input");
    	$content = $this->xmlToArray($content);
    	$product_id = $content['product_id'];

    	$project = D('Record')->field('re_pr_name,re_order_num,re_order_fee')
    	->where(array('re_id'=>$product_id))->find();

    	$parameters["appid"] = $this->appid;
		$parameters["mch_id"] = $this->mechid;
		$parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];
		$parameters["nonce_str"] = $this->createNoncestr();
		$parameters["body"] = '小琢骨健康-'.$project['re_pr_name'];
		$parameters["out_trade_no"] = $project['re_order_num'];
		$parameters["total_fee"] = $project['re_order_fee']*100;
		$parameters["notify_url"] = $this->notify_url;
		$parameters["trade_type"] = 'NATIVE';
		$parameters["product_id"] = $product_id;
		$parameters["sign"] = $this->getSign($parameters);
    	$xml = $this->arrayToXml($parameters);
		$res = $this->postXmlCurl($xml,$this->api,30);
		$res = $this->xmlToArray($res);
		if( ($res['return_code'] == 'SUCCESS') && ($res['result_code'] == 'SUCCESS') ){
			$prepay_id = $res['prepay_id'];

			$jsApiObj["return_code"] = 'SUCCESS';
			$jsApiObj["appid"] = $this->appid;
			$jsApiObj["mch_id"] = $this->mechid;
			$jsApiObj["nonce_str"] = $this->createNoncestr();
			$jsApiObj["prepay_id"] = $prepay_id;
			$jsApiObj["result_code"] = 'SUCCESS';
			$jsApiObj["sign"] = $this->getSign($jsApiObj);
			$xml = $this->arrayToXml($jsApiObj);
			echo $xml;
		}	

    }




}
  
?>
