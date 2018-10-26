<?php
namespace Home\Controller;
use Think\Controller;

class WeixinController extends  Controller{

    public function __construct(){
        parent::__construct();
        $this->appid = 'wx21dec988821cc8ea';
        $this->appsecret = '095d2ebf3d8faeb13cd34b79d3f60ffb';
    }

    //获取access_token
    public function get_access_token(){
        $access_token = S('qianyan_accesstoken');
        if(empty($access_token)){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->appsecret";
            $res = $this->http_request($url);
            $res = json_decode($res,true);
            $access_token = $res['access_token'];
            S('qianyan_accesstoken',null);
            S('qianyan_accesstoken',$access_token,'3600');
        }
        return $access_token;
    }

    /*
    //
    //
    //微信jssdk相关接口
    //
    //
    */

    //配置jssdk
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
          "appId"     => $this->appid,
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          // "url"       => $url,
          "signature" => $signature,
          // "rawString" => $string
        );
        return $signPackage; 
    }

    //生成随机数
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getJsApiTicket() {
        $ticket = S('qianyan_ticket');
        if(empty($jsapi_ticket)){
          $accessToken = $this->get_access_token();
          $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
          $res = json_decode($this->http_request($url));
          $ticket = $res->ticket;
          S('qianyan_ticket',null);
          S('qianyan_ticket',$ticket,'3600');
        }
        return $ticket;
    }

    /*
    //
    //
    //网页授权相关接口
    //
    //
    */
    //scope参数为snsapi_base，只获取用户openid，静默授权
    public function oauth2_snsapi_base($redirect_url,$state = NULL){
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=snsapi_base&state=".$state."#wechat_redirect";
        return $url;
    }

    //scope参数为snsapi_userinfo，可抓取用户的基本信息，但需要用户授权（如果用户已关注公众号，则无需授权）
    public function oauth2_snsapi_userinfo($redirect_url,$state = NULL){
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=snsapi_userinfo&state=".$state."#wechat_redirect";
        return $url;
    }

    //通过code换取网页授权access_token
    //网页授权的作用域为snsapi_base，则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止。
    public function oauth2_access_token($code){
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //如果网页授权作用域为snsapi_userinfo，则此时开发者可以通过access_token（网页授权的access——token）和openid拉取用户信息了。
    //可获取未关注用户的基本信息。
    public function oauth2_get_user_info($access_token, $openid){
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    /*
    //
    //
    //公众号菜单接口相关设置
    //
    //
    */
   
    //创建自定义菜单接口   /微信服务器有时不响应调用失败
    public function create_menu($menu){
        $access_token = $this->get_access_token();      
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $res = $this->http_request($url,$menu);
        return $res;
    }

    //查询自定义菜单接口,自定义菜单查询接口则仅能查询到使用API设置的菜单配置
    public function get_menu(){
        $access_token = $this->get_access_token();            
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //删除自定义菜单接口,使用普通自定义菜单删除接口可以删除所有自定义菜单（包括默认菜单和全部个性化菜单)
    public function delete_menu(){
        $access_token = $this->get_access_token();            
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //个性化菜单设置接口,创建个性化菜单之前必须先创建默认菜单
    public function addconditional($data){
        $access_token = $this->get_access_token();            
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //个性化菜单删除接口
    public function delconditional(){
        //menuid为菜单id，可以通过自定义菜单查询接口获取。
        $data = '{"menuid":"426131561"}';
        $access_token = $this->get_access_token();            
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true); 
    }

    //获取自定义菜单配置接口,获取默认菜单和全部个性化菜单信息
    public function get_current_selfmenu_info(){
        $access_token = $this->get_access_token();            
        $url = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=".$access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    /*
    //
    //
    //公众号素材管理相关接口
    //
    //

    */
    //新增临时素材接口    //curl问题未解决
    public function create_media($file,$type){
        $data['media'] = $file;
        // $data = array("media" => "@E:\phpstudy\PHPTutorial\WWW\upload\img\wutu.jpg");
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$access_token&type=$type";    
        $res = $this->http_request($url,$file);
        return json_decode($res, true);
    }

    //获取临时素材接口
    public function get_media($MEDIA_ID){
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$MEDIA_ID;
        $res = $this->downloadWeixinFile($url);

        // $history_order = file_get_contents('./mediaid.txt');
        // $history_order = $history_order."\r\n-\r\n".$res['header']['Date'];
        // file_put_contents('./mediaid.txt', $history_order);

        //获取毫秒数
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        //保存素材至public
        $filename = 'E:/phpstudy/PHPTutorial/WWW/apily/Public/weixin/'.$msectime.'.jpg';
        $this->saveWeixinFile($filename,$res['body']);
    }

    //新增永久素材接口
    public function create_yongjiu_media($MEDIA_ID){

    }




    /*
    //
    //
    //公众号用户管理接口相关设置
    //
    //
    */
   
    //创建用户标签
    public function create_tags($data){
        $data = '{"tag":{"name" : "上海"}}';
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/create?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //获取公众号已创建的标签
    public function get_tags(){
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token=".$access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //编辑标签
    public function update_tags(){
        $data = '{"tag":{"id":101,"name":"上海人"}}';
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/update?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //删除标签
    public function del_tags(){
        $data = '{   "tag":{        "id" : 100   } }';
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/delete?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //获取标签下粉丝列表
    public function get_tag_user(){
        $data = '{   "tagid" : 101,   "next_openid":""}';  //第一个拉取的OPENID，不填默认从头开始拉取 
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/user/tag/get?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return jsonToArray($res);
    }

    //1. 批量为用户打标签
    public function create_tag_user(){
        $data = '{"openid_list" : 
                    [ 
                        "ocYxcuAEy30bX0NXmGn4ypqx3tI0", 
                        "ocYxcuBt0mRugKZ7tGAHPnUaOW7Y"   
                    ],   
                    "tagid" : 101 
                }';  
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //2.批量为用户取消标签
    public function del_tag_user(){
        $data = '{"openid_list" : 
                    [ 
                        "ocYxcuAEy30bX0NXmGn4ypqx3tI0", 
                        "ocYxcuBt0mRugKZ7tGAHPnUaOW7Y"   
                    ],   
                    "tagid" : 101 
                }';  
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }

    //3.获取用户身上的标签列表
    public function get_user_tags(){
        $data = '{   "openid" : "o_wcJv9eKHwn5yi4h5LXf0HbADxo" }';  
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/tags/getidlist?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true); 
    }

    //设置用户备注名
    public function updateremark(){
        $data = '{"openid":"o_wcJv9eKHwn5yi4h5LXf0HbADxo", "remark":"帅哥"}';  
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/updateremark?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true); 
    }

    //获取用户基本信息（包括UnionID机制）
    public function get_user_info($openid){
        $access_token = $this->get_access_token();        
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //批量获取用户基本信息,最多支持一次拉取100条。
    public function get_users_infos(){
        $data = '{
                    "user_list": [
                        {
                            "openid": "o_wcJv9eKHwn5yi4h5LXf0HbADxo", 
                            "lang": "zh_CN"
                        }, 
                        {
                            "openid": "o_wcJvyuIGvxnT7jdWLSPYsEntBg", 
                            "lang": "zh_CN"
                        }
                    ]
                }';  
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=".$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true); 
    }

    //获取用户列表
    public function get_user_list(){
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=$access_token&next_openid=";
        $res = $this->http_request($url);
        return json_decode($res, true); 
    }

    //拉黑用户
    //取消拉黑用户
    //获取拉黑列表


    /*
    //
    //
    //公众号账号管理相关接口设置
    //
    //
    */

    //生成带参数的二维码
    //临时二维码，是有过期时间的，最长可以设置为在二维码生成后的30天（即2592000秒）后过期，但能够生成较多数量。临时二维码主要用于帐号绑定等不要求二维码永久保存的业务场景
    public function qr_scene($data){
        
        // $data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
        
        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        $res = $this->http_request($url,$data);

        $res = json_decode($res, true); 

        return $res['ticket'];
    }

    //永久二维码，是无过期时间的，但数量较少（目前为最多10万个）。永久二维码主要用于适用于帐号绑定、用户来源统计等场景。
    //下载到本地
    public function qr_limit_scene($data){

        // {"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}

        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        $res = $this->http_request($url,$data);

        $res = json_decode($res, true); 
        
        $qr_code_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$res['ticket'];
        $img = $this->downloadWeixinFile($qr_code_url);

        $filename = 'E:/phpstudy/PHPTutorial/WWW/apily/Public/weixin/'.date('YmdHis',time()).'.jpg';
        $this->saveWeixinFile($filename,$img['body']);
        return true;
    }

    //长链接转短链接接口
    public function shorturl(){
        $data = '{"action": "long2short", "long_url": "http://miyacloud.cn/apily/index.php/Index/test"}';

        $access_token = $this->get_access_token();              
        $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=$access_token";
        $res = $this->http_request($url,$data);
        $res = json_decode($res, true); 
        return $res;
    }

    /*
    //
    //
    //公众号数据统计相关接口
    //
    //
    */
    
    


    /*
    //
    //
    //公众号客服接口相关设置(请注意，必须先在公众平台官网为公众号设置微信号后才能使用该能力。)
    //
    //
    */
    //新增客服账号
    public function add_kfaccount(){
        $data = '{"kf_account" : "test", "nickname" : "客服1", "password" : "123",}';
        $access_token = $this->get_access_token();  
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/add?access_token='.$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }


    //获取永久素材列表接口
    public function get_sucai(){
        $data['type'] = 'news';
        $data['offset'] = '0';
        $data['count'] = '20';
        $data = json_encode($data);
        $access_token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }




    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    public function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //下载图片
    function downloadWeixinFile($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);    
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header' => $httpinfo), array('body' => $package)); 
        return $imageAll;
    }

    //保存图片
    function saveWeixinFile($filename, $filecontent)
    {
        $local_file = fopen($filename, 'w');
        if (false !== $local_file){
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
            }
        }
    }


}

