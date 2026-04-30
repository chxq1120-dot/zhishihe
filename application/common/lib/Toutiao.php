<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/11/13
 * Time: 18:24
 */

namespace app\common\lib;

use think\Controller;
use think\Exception;

class Toutiao extends Controller
{
    protected $app_id;
    protected $app_secret;
    protected $private_key;
    protected $public_key;

    public function __construct()
    {
        parent::__construct();
        $this->app_id = config('setting.tt_appid');
        $this->app_secret = config('setting.tt_appsecret');
        $this->private_key = config('setting.tt_private_key');
        $this->public_key= config('setting.tt_private_os');
    }
    /**
     * 获取access_token
     */
    public function getToken()
    {
        $access_token = cache('access_token');
        if (empty($access_token)) {
            $result = httpRequest('https://developer.toutiao.com/api/apps/v2/token', 'POST', ['appid' => $this->app_id, 'secret' => $this->app_secret, 'grant_type' => 'client_credential'], [], false, true);
            $result = json_decode($result, true);
            if ($result['err_no'] !== 0) {
                return [false, $result['err_tips']];
            }
            $access_token = $result['data']['access_token'];
            cache('access_token', $access_token, $result['data']['expires_in'] - 10);
        }
        return [true, $access_token];
    }

    /**
     * 获取openid
     */
    public function getOpenId($code)
    {
        $result = httpRequest('https://developer.toutiao.com/api/apps/v2/jscode2session', 'POST', ['appid' => $this->app_id, 'secret' => $this->app_secret, 'code' => $code], [], false, true);
        $result = json_decode($result, true);
        if ($result['err_no'] !== 0) {
            return $result['err_tips'];
        }
        return $result['data'];
    }

    public function newNotify($params,$timestamp,$nonce,$signature)
    {
        $result=$this->verify(json_encode($params), $timestamp, $nonce, $signature);
        if(empty($result)){
            return [false, '验证失败'];
        }
        $params=json_decode($params['msg'],true);
        return [true, $params];
    }
    /**
     * 支付回调
     */
    public function notify($params)
    {
        $params = json_decode($params, true);
        $data=[
            config('setting.tt_token'),
            $params['timestamp'],
            $params['nonce'],
            $params['msg'],
        ];
        sort($data, 2);
        doSyslog($data, 'Notify');
        $sign = sha1(implode('',$data));
        if ($sign !== $params['msg_signature']) {
            doSyslog($sign.'@'.$params['msg_signature'].'@签名不符', 'ttNotify');
            return [false, '签名失败'];
        }
        $result = json_decode($params['msg'], true);
        return [true, $result];
    }
    /**
     * 订单分账
     */
    public function accountSettle($order)
    {
        $order['app_id'] = $this->app_id;
        $order['settle_desc'] = '主动结算';
        $order['notify_url'] = $this->request->domain()."/index/Notify/ttSettle";
        $order['sign'] = $this->getSign($order);
        $result = httpRequest('https://developer.toutiao.com/api/apps/ecpay/v1/settle', 'POST', $order, [], false, true);
        $result = json_decode($result, true);
        if ($result['err_no'] !== 0) {
            return [false, $result['err_tips']];
        }
        return [true, $result['settle_no']];
    }
    /**
     * 发起预支付下单
     */
    public function createOrder($order)
    {
        $order['app_id'] = $this->app_id;
        $order['valid_time'] = 30 * 60;
        $order['notify_url'] = config('setting.tt_notify');
        $order['sign'] = $this->getSign($order);
        $result = httpRequest('https://developer.toutiao.com/api/apps/ecpay/v1/create_order', 'POST', $order, [], false, true);
        doSyslog($result,'createOrder');
        $result = json_decode($result, true);
        if ($result['err_no'] !== 0) {
            return $result['err_tips'];
        }
        return $result['data'];
    }

    /**
     * 推送订单同步
     * @return array
     */
    public function pushOrder($params)
    {
        list($result, $access_token) = $this->getToken();
        if (!$result) {
            return [false, $access_token];
        }
        $data=[
            'access_token'=>$access_token,
            'app_name'=>'douyin',
            'open_id'=>$params['open_id'],
            'order_detail'=>json_encode($params['order_detail']),
            'order_status'=>$params['order_status'],
            'order_type'=>0,
            'update_time'=>$params['utime']
        ];
        $result = httpRequest('https://developer.toutiao.com/api/apps/order/v2/push', 'POST', $data, [], false, true);
        doSyslog($result,'pushOrder');
        $result = json_decode($result, true);
        if ($result['err_code'] !== 0) {
            return [false,$result['err_msg']];
        }
        return [true,'success'];
    }
    /**
     * 支付签名
     */
    protected function getSign($map)
    {
        $rList = array();
        foreach ($map as $k => $v) {
            if ($k == "other_settle_params" || $k == "msg_signature" || $k == "app_id" || $k == "sign" || $k == "thirdparty_id")
                continue;
            $value = trim(strval($v));
            $len = strlen($value);
            if ($len > 1 && substr($value, 0, 1) == "\"" && substr($value, $len, $len - 1) == "\"")
                $value = substr($value, 1, $len - 1);
            $value = trim($value);
            if ($value == "" || $value == "null")
                continue;
            array_push($rList, $value);
        }
        array_push($rList, config('setting.tt_secret'));
        sort($rList, 2);
        return md5(implode('&', $rList));
    }

    /**
     * 生成小程序码
     */
    public function getQrcode($params)
    {
        list($result, $access_token) = $this->getToken();
        if (!$result) {
            return [false, $access_token];
        }
        $params['access_token'] = $access_token;
        $params['appname'] = 'douyin';
        $params['set_icon'] = true;
        $result = httpRequest('https://developer.toutiao.com/api/apps/qrcode', 'POST', $params, [], false, true);
        if (stripos($result, 'err_no') !== false || stripos($result, 'errcode') !== false) {
            $result = json_decode($result, true);
            return [false, $result['errmsg']];
        }
        return [true, $result];
    }
    /**
     *====================
     * 通用交易系统
     * ===================
     */
    public function getByteAuth($data)
    {
        try {
            $nonceStr=$this->randStr(10);
            $timestamp=time();
            $keyVersion='2';
            $byteAuthorization = $this->getByteAuthorization($this->private_key, $data, $this->app_id, $nonceStr, $timestamp, $keyVersion);
            return [true,$byteAuthorization];
        }catch(Exception $e){
            return [false,$e->getMessage()];
        }
    }
    /**
     * 获取Authorization签名数据
     * @param $privateKeyStr
     * @param $data
     * @param $appId
     * @param $nonceStr
     * @param $timestamp
     * @param $keyVersion
     * @return string|null
     */
    protected function getByteAuthorization($privateKeyStr, $data, $appId, $nonceStr, $timestamp, $keyVersion) {
        $byteAuthorization = '';
        // 读取私钥
        $privateKey = openssl_pkey_get_private($privateKeyStr);
        if (!$privateKey) {
            throw new InvalidArgumentException("Invalid private key");
        }
        // 生成签名
        $signature = $this->getSignature("POST", "/requestOrder", $timestamp, $nonceStr, $data, $privateKey);
        if ($signature === false) {
            return null;
        }
        // 构造 byteAuthorization
        $byteAuthorization = sprintf("SHA256-RSA2048 appid=%s,nonce_str=%s,timestamp=%s,key_version=%s,signature=%s", $appId, $nonceStr, $timestamp, $keyVersion, $signature);
        return $byteAuthorization;
    }
    /**
     * 获取签名字符串
     * @param $method
     * @param $url
     * @param $timestamp
     * @param $nonce
     * @param $data
     * @param $privateKey
     * @return string
     */
    protected function getSignature($method, $url, $timestamp, $nonce, $data, $privateKey) {
        //printf("method:%s\n url:%s\n timestamp:%s\n nonce:%s\n data:%s", $method, $url, $timestamp, $nonce, $data);
        $targetStr = $method. "\n" . $url. "\n" . $timestamp. "\n" . $nonce. "\n" . $data. "\n";
        openssl_sign($targetStr, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 随机字符串
     * @param $length
     * @return string
     */
    protected function randStr($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 验签
     * @param $http_body
     * @param $timestamp
     * @param $nonce_str
     * @param $sign
     * @return bool|null
     */
    public function verify($http_body, $timestamp, $nonce_str, $sign)
    {
        $data = $timestamp . "\n" . $nonce_str . "\n" . $http_body . "\n";
        $publicKey = $this->public_key;
        if (!$publicKey) {
            return null;
        }
        $res = openssl_get_publickey($publicKey); // 注意验签时publicKey使用平台公钥而非应用公钥
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        return $result;  //bool
    }
}