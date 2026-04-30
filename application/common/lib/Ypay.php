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

class Ypay extends Controller
{
    protected $app_mchid;
    protected $app_key;
    protected $api_url;

    public function __construct()
    {
        parent::__construct();

        $this->api_url['wechat'] = config('setting.ypay_api_url');
        $this->app_mchid['wechat'] = config('setting.ypay_api_mchid');
        $this->app_key['wechat'] = config('setting.ypay_api_key');

        $this->api_url['alipay'] = config('setting.alipay_ypay_url');
        $this->app_mchid['alipay'] = config('setting.alipay_ypay_mchid');
        $this->app_key['alipay'] = config('setting.alipay_ypay_key');

    }

    /**
     * 发送支付
     * @param $payment string 支付通道 wechat微信，alipay支付宝
     * @param $paytype int 支付类型
     * @param $ordno string 订单号
     * @param $total_fee float 支付金额
     * @param $body string 支付标题
     * @param $notify_url string 异步通知地址
     * @param $nopay_url string 未支付通知地址
     * @param $return_url string 同步通知地址
     * @return array 返回结果
     */
    public function createPay($payment, $paytype, $ordno, $total_fee, $body = '', $notify_url = '', $nopay_url = '', $return_url = '')
    {
        $data = [
            'appid' => $this->app_mchid[$payment],
            'mch_orderid' => $ordno,
            'description' => $body,
            'total' => $total_fee,
            'payType' => $paytype,
            'notify_url' => $notify_url,
            'nopay_url' => $nopay_url,
            'callback_url' => $return_url,
            'time' => time(),
            'nonce_str' => rand_string(10, 0),
        ];
        $data['sign'] = $this->createSign($data, $this->app_key[$payment]);
        $result = httpRequest($this->api_url[$payment], 'POST', $data);
        $result = json_decode($result, true);
        if ($result['code'] !== 0) {
            return [false, $result['msg']];
        }
        return [true, ['payurl' =>$result['url']]];
    }

    /**
     * 生成签名字符串
     * @param $data array 参数
     * @param $app_key string 密钥
     * @return string
     */
    protected function createSign($data, $app_key)
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
        return hash('sha256', rtrim($string,'&') . $app_key, false);
    }
    /**
     * 支付回调
     */
    public function notify($data, $payment)
    {
        try {
            $sign = $this->createSign($data, $this->app_key[$payment]);
            if ($sign !== $data['sign']) {
                return [false, '签名错误'];
            }
            if ($data['state'] !== 'SUCCESS') {
                return [false, '订单未支付'];
            }
            return [true, $data];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

}