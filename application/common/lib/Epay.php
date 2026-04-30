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

class Epay extends Controller
{
    protected $app_mchid;
    protected $app_key;
    protected $api_url;
    protected $app_type;

    public function __construct()
    {
        parent::__construct();

        $this->api_url['wechat'] = config('setting.epay_api_url');
        $this->app_mchid['wechat'] = config('setting.epay_api_mchid');
        $this->app_key['wechat'] = config('setting.epay_api_key');
        $this->app_type['wechat'] = 'wxpay';

        $this->api_url['alipay'] = config('setting.alipay_epay_url');
        $this->app_mchid['alipay'] = config('setting.alipay_epay_mchid');
        $this->app_key['alipay'] = config('setting.alipay_epay_key');
        $this->app_type['alipay'] = 'alipay';

    }

    /**
     * 发送支付
     * @param $payment string 支付方式 wechat微信，alipay支付宝
     * @param $ordno string 订单号
     * @param $total_fee float 支付金额
     * @param $body string 支付标题
     * @param $notify_url string 异步通知地址
     * @param $return_url string 同步通知地址
     * @return array 返回结果
     */
    public function createPay($payment, $ordno, $total_fee, $body = '', $notify_url = '', $return_url = '')
    {
        $data = [
            'pid' => $this->app_mchid[$payment],
            'type' => $this->app_type[$payment],
            'out_trade_no' => $ordno,
            'total_fee' => $total_fee,
            'name' => $body,
            'money' => $total_fee,
            'clientip' => request()->ip(0, true),
            'notify_url' => $notify_url,
            'return_url' => $return_url,
        ];
        $data['sign'] = $this->createSign($data, $this->app_key[$payment]);
        $data['sign_type'] = 'MD5';
        return [true, ['payurl'=>$this->api_url[$payment].'?'.http_build_query($data)]];
    }

    /**
     * 生成签名字符串
     * @param $data array 参数
     * @param $app_secret string 密钥
     * @return string
     */
    protected function createSign($data, $app_key)
    {
        ksort($data);
        $string = '';
        foreach ($data as $key => $val) {
            if ($key == 'sign' || $key == 'sign_type' || is_null($val) || $val === '') {
                continue;
            }
            $string .= "$key=$val&";
        }
        return md5(rtrim($string, '&') . $app_key);
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
            if ($data['trade_status'] !== 'TRADE_SUCCESS') {
                return [false, '订单未支付'];
            }
            return [true, $data];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

}