<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {

    //配置js-sdk
    public function index(){
    	$res = R('Weixin/getSignPackage');

    	$this->appId = $res['appId'];
    	$this->timestamp = $res['timestamp'];
    	$this->nonceStr = $res['nonceStr'];
    	$this->signature = $res['signature'];

        $this->display();
    }


    

    public function get_access_token(){
        $get_access_token = R('weixin/get_access_token');
        var_dump($get_access_token);
    }


    public function oauth2(){
        if (!isset($_GET["code"])){
            $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $jumpurl = R('Weixin/oauth2_snsapi_base',array($redirect_url,'123'));
            Header("Location: $jumpurl");
        }else{
            $oauth2= R('Weixin/oauth2_access_token', array($_GET['code']));
            var_dump($oauth2['openid']);
        }
    }


    //创建自定义菜单
    public function create_menu(){
        $jsonmenu = '{"button": [
        {"name": "扫码", "sub_button": [{"type": "scancode_waitmsg", "name": "扫码带提示", "key": "rselfmenu_0_0"}, {"type": "scancode_push", "name": "扫码推事件", "key": "rselfmenu_0_1"}]}, { "name": "发图", "sub_button": [{"type": "pic_photo_or_album", "name": "拍照或者相册发图", "key": "rselfmenu_1_1"}]}, {"name": "其他", "sub_button": [{"name": "发送位置", "type": "location_select", "key": "rselfmenu_2_0"} ]}
            ]}';
        $res = R('Weixin/create_menu',array($jsonmenu));
        var_dump($res);
    }


    public function test(){
        
        // echo createNoncestr();
        $res = R('Weixin/get_tag_user');
        var_dump($res);
    }


    //获取临时素材
    public function media_id(){ 
        $img = 
        $content = file_get_contents("php://input");
        $medias = json_decode($content,true);
        for($i=0; $i<count($medias); $i++){
            $mediaid = $medias[$i];
            R('Weixin/get_media',array($mediaid));
        }
        $res = 'success';
        $this->ajaxReturn($res);
    }


}
  
?>
