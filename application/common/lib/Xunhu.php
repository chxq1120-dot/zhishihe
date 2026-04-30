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

class Xunhu extends Controller
{
    protected $app_id;
    protected $app_secret;
    protected $api_url;
    protected $app_domain;
    protected $app_name;
    protected $app_appid;
    public function __construct()
    {
        parent::__construct();
        $this->app_id['wechat'] = config('setting.xunhu_appid');
        $this->app_secret['wechat'] = config('setting.xunhu_secret');
        $this->api_url['wechat'] = config('setting.xunhu_api_url');
        $this->app_domain['wechat'] = config('setting.xunhu_domain');
        $this->app_name['wechat'] = config('setting.xunhu_name');

        $this->app_id['wxmini'] = config('setting.wxnini_xunhu_appid');
        $this->app_secret['wxmini'] = config('setting.wxnini_xunhu_secret');
        $this->api_url['wxmini'] = config('setting.wxnini_xunhu_url');
        $this->app_appid['wxmini'] = config('setting.wxnini_xunhu_miniappid');

        $this->app_id['alipay'] = config('setting.xunhu_ali_appid');
        $this->app_secret['alipay'] = config('setting.xunhu_ali_secret');
        $this->api_url['alipay'] = config('setting.xunhu_ali_api_url');
        $this->app_domain['alipay'] = config('setting.xunhu_ali_domain');
        $this->app_name['alipay'] = config('setting.xunhu_ali_name');
    }

    /**
     * 生成小程序微信支付
     * @param $payment string 支付方式 wechat微信，alipay支付宝
     * @param $type string 通道类型 H5支付固定值"WAP"，小程序支付固定值"JSAPI"
     * @param $ordno string 订单号
     * @param $total_fee float 支付金额
     * @param $body string 支付标题
     * @param $notify_url string 异步通知地址
     * @return array 返回结果
     */
    public function createWechatMiniPay($payment, $type, $ordno, $total_fee, $body = '', $notify_url = '')
    {
        $data = [
            'version' => '1.1',
            'appid' => $this->app_id[$payment],
            'trade_order_id' => $ordno,
            'total_fee' => $total_fee,
            'type' => $type,
            'wap_url' => $this->api_url[$payment],
            'title' => $body,
            'time' => time(),
            'nonce_str' => rand_string(16),
            'notify_url' => $notify_url,
            'attach'=>$payment
        ];
        $data['hash'] = $this->createSign($data, $this->app_secret[$payment]);
        return [true, ['data'=>$data,'appid'=>$this->app_appid[$payment]]];
    }
    /**
     * 发送支付
     * @param $payment string 支付方式 wechat微信，alipay支付宝
     * @param $type string 通道类型 H5支付固定值"WAP"，小程序支付固定值"JSAPI"
     * @param $ordno string 订单号
     * @param $total_fee float 支付金额
     * @param $body string 支付标题
     * @param $notify_url string 异步通知地址
     * @param $return_url string 同步通知地址
     * @param $callback_url string 错误跳转地址
     * @return array 返回结果
     */
    public function createPay($payment, $type, $ordno, $total_fee, $body = '', $notify_url = '', $return_url = '', $callback_url = '')
    {
        $data = [
            'version' => '1.1',
            'lang' => 'zh-cn',
            'appid' => $this->app_id[$payment],
            'plugins' => 'zhipall_wcce_v2',
            'trade_order_id' => $ordno,
            'total_fee' => $total_fee,
            'payment' => $payment,
            'type' => $type,
            'wap_url' => $this->app_domain[$payment],
            'wap_name' => $this->app_name[$payment],
            'title' => $body,
            'time' => time(),
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            'callback_url' => $callback_url,
            'nonce_str' => rand_string(16),
        ];
        $data['hash'] = $this->createSign($data, $this->app_secret[$payment]);
        try {
            $response = httpRequest($this->api_url[$payment], 'POST', $data);
            $result = $response ? json_decode($response, true) : null;
            if (!$result) {
                return [false, '请求错误'];
            }
            if ($result['errcode'] != 0) {
                return [false, $result['errmsg']];
            }
            return [true, $result];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 生成签名字符串
     * @param $data array 参数
     * @param $app_secret string 密钥
     * @return string
     */
    protected function createSign($data, $app_secret)
    {
        ksort($data);
        reset($data);
        $string = '';
        foreach ($data as $key => $val) {
            if ($key == 'hash' || is_null($val) || $val === '') {
                continue;
            }
            $string .= "$key=$val&";
        }
        return md5(rtrim($string, '&') . $app_secret);
    }

    /**
     * 支付回调
     */
    public function notify($data, $payment)
    {
        try {
            $sign = $this->createSign($data, $this->app_secret[$payment]);
            if ($sign !== $data['hash']) {
                return [false, '签名错误'];
            }
            if ($data['status'] !== 'OD') {
                return [false, '订单状态未支付'];
            }
            return [true, $data];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

}