<?php

namespace app\common\model;

use think\Model;

class WxPay extends Model
{
    /**
     * 微信支付配置信息
     * @var array
     */
    private $config = [];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->config['appid'] = config('setting.app_id');
        $this->config['appsecret'] = config('setting.app_secret');
        $this->config['mch_id'] = config('setting.mch_id');
        $this->config['api_key'] = config('setting.pay_weixin_v3_key');#V3密钥
        $this->config['client_key'] = config('setting.client_key');
        $this->config['client_cert'] = config('setting.client_cert');
        $this->config['serial_no'] = config('setting.cash_weixin_serial_no');#证书序列号
    }
    /**
     * 批量商家转账
     * @param $body
     * @return array
     */
    public function payBatcheBills($body)
    {
        $params = [
            'mch_id' => $this->config['mch_id'],
            'url' => 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills',
            'method' => 'POST',
            'timestamp' => time(),
            'nonce' => rand_string(16),
            'serial_no' => $this->config['serial_no'],
            'body' => $body
        ];
        #签名
        $authSign = $this->createSignStr($params);
        $result = httpRequest($params['url'], $params['method'], $params['body'], ['Authorization: ' . $authSign,'Wechatpay-Serial: '.$params['serial_no'],'Accept: application/json'], false, true);
        $result = json_decode($result, true);
        doSyslog($result, 'payBatcheBills');
        if (!isset($result['code'])) {
            return [true, $result];
        } else {
            return [false, $result['message']];
        }
    }
    /**
     * 批量商家转账到零钱
     * @param $body
     * @return array
     */
    public function payBatches($body)
    {
        $params = [
            'mch_id' => $this->config['mch_id'],
            'url' => 'https://api.mch.weixin.qq.com/v3/transfer/batches',
            'method' => 'POST',
            'timestamp' => time(),
            'nonce' => rand_string(16),
            'serial_no' => $this->config['serial_no'],
            'body' => $body
        ];
        #签名
        $authSign = $this->createSignStr($params);
        $result = httpRequest($params['url'], $params['method'], $params['body'], ['Authorization: ' . $authSign,'Wechatpay-Serial: '.$params['serial_no'],'Accept: application/json'], false, true);
        $result = json_decode($result, true);
        doSyslog($result, 'payBatches');
        if (!isset($result['code'])) {
            return [true, $result];
        } else {
            return [false, $result['message']];
        }
    }
    /**
     * 构造微信支付V3签名
     */
    protected function createSignStr($params)
    {
        $url_parts = parse_url($params['url']);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message = $params['method'] . "\n" . $canonical_url . "\n" . $params['timestamp'] . "\n" . $params['nonce'] . "\n" . json_encode($params['body']) . "\n";
        openssl_sign($message, $raw_sign, openssl_get_privatekey($this->config['client_key']), 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $params['mch_id'], $params['nonce'], $params['timestamp'], $params['serial_no'], $sign);
        return $schema . ' ' . $token;
    }


    /**
     * 商家转账到零钱回调
     * @param array 参数
     * @return array
     */
    public function notify($params)
    {
        $inSignature = $params['signature'];// 请根据实际情况获取
        $inTimestamp = $params['timestamp'];// 请根据实际情况获取
        $inSerial = $params['serial'];// 请根据实际情况获取
        $inNonce = $params['nonce'];// 请根据实际情况获取
        $inBody = $params['body'];
        $publicKey=openssl_get_publickey($this->config['client_cert']);
        #构造验签名串
        $verify_str=implode("\n", array_merge([$inTimestamp,$inNonce,$inBody], ['']));
        #验证签名
        $verifyStatus=$this->verifySign($verify_str,$inSignature,$publicKey);
        if($verifyStatus){
            $inBodyArray = json_decode($inBody, true);
            $ciphertext=$inBodyArray['resource']['ciphertext'];
            $nonce=$inBodyArray['resource']['nonce'];
            $aad=$inBodyArray['resource']['associated_data'];
            #解密报文
            list($res,$inBodyRes)=$this->decrypt($ciphertext,$this->config['api_key'],$nonce,$aad);
            if(!$res){
                return [false,$inBodyRes];
            }
            $inBodyArr = json_decode($inBodyRes, true);
            return [true,$inBodyArr];
        }
        return [false,'签名验证失败'];
    }

    /**
     * 验证签名
     * @param $message string 构造签名数据
     * @param $inSignature string 验证签名
     * @param $publicKey string 证书
     * @return bool
     */
    protected function verifySign($message,$inSignature,$publicKey)
    {
        $result=openssl_verify($message, base64_decode($inSignature), $publicKey, OPENSSL_ALGO_SHA256);
        if(!$result){
            return false;
        }
        return true;
    }

    /**
     * 解密数据
     * @param $ciphertext string 密文
     * @param $key string 密钥
     * @param $iv string 随机码
     * @param $aad string 关联数据
     * @return array
     */
    protected function decrypt($ciphertext, $key, $iv, $aad)
    {
        $ciphertext = base64_decode($ciphertext);
        $authTag = substr($ciphertext, $tailLength = 0 - 16);
        $tagLength = strlen($authTag);
        if ($tagLength > 16 || ($tagLength < 12 && $tagLength !== 8 && $tagLength !== 4)) {
            return [false,' `$ciphertext`不完整'];
        }
        $plaintext = openssl_decrypt(substr($ciphertext, 0, $tailLength), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $authTag, $aad);
        if (false === $plaintext) {
            return [false,'请检查密钥是否正确'];
        }
        return [true,$plaintext];
    }
}
