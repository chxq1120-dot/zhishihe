<?php

namespace alipay;
class wap
{
    protected $appId;
    protected $charset;
    protected $returnUrl;
    protected $notifyUrl;
    //支付宝公钥
    protected $alipayCertPublic;
    //应用公钥
    protected $appCertContent;
    //支付宝根证书
    protected $alipayRootContent;
    //应用私钥值
    protected $appPrivateKey;
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;
    protected $payProName = 'alipay.trade.wap.pay'; //接口名称
    protected $payProCode = 'QUICK_WAP_WAY'; //接口代码
    protected $refundProName = 'alipay.trade.refund';//退款接口名称
    protected $refundFee;
    protected $refundReason;
    protected $outRequestNo;
    //转账到支付宝接口
    protected $transProName = 'alipay.fund.trans.uni.transfer';
    protected $outBizNo;//转账订单号
    protected $transAmount;//转账金额
    protected $orderTitle;//转账标题
    protected $remark;//转账说明
    protected $payeeInfo;//收款人信息
    //分账接口
    protected $settleProName = 'alipay.trade.order.settle';
    protected $outReqNo;//结算请求流水号
    protected $tradeNo;//支付宝订单号
    protected $royaltyParameters;

    public function __construct()
    {
        $this->charset = 'UTF-8';
    }

    public function setAppid($appid)
    {
        $this->appId = $appid;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function setAlipayCertPublic($alipayCertPublic)
    {
        $this->alipayCertPublic = $alipayCertPublic;
    }

    public function setAlipayRootContent($alipayRootContent)
    {
        $this->alipayRootContent = $alipayRootContent;
    }

    public function setAppCertContent($appCertContent)
    {
        $this->appCertContent = $appCertContent;
    }

    public function setRsaPrivateKey($appPrivateKey)
    {
        $this->appPrivateKey = $appPrivateKey;
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function setTotalFee($payAmount)
    {
        $this->totalFee = $payAmount;
    }

    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }

    public function setOrderName($orderName)
    {
        $this->orderName = $orderName;
    }

    public function setProName($proName)
    {
        $this->payProName = $proName;
    }

    public function setProCode($proCode)
    {
        $this->payProCode = $proCode;
    }

    public function setOutRequestNo($outRequestNo)
    {
        $this->outRequestNo = $outRequestNo;
    }

    public function setRefundFee($refundFee)
    {
        $this->refundFee = $refundFee;
    }

    public function setRefundReason($refundReason)
    {
        $this->refundReason = $refundReason;
    }

    //转账订单号
    public function setOutBizNo($outBizNo)
    {
        $this->outBizNo = $outBizNo;
    }

    //转账金额
    public function setTransAmount($transAmount)
    {
        $this->transAmount = $transAmount;
    }

    //转账标题
    public function setOrderTitle($orderTitle)
    {
        $this->orderTitle = $orderTitle;
    }

    //转账说明
    public function setRemark($remark)
    {
        $this->remark = $remark;
    }
    //设置结算请求流水号
    public function setOutReqNo($outReqNo)
    {
        $this->outReqNo = $outReqNo;
    }
    //设置支付宝订单号
    public function setTradeNo($tradeNo)
    {
        $this->tradeNo = $tradeNo;
    }
    //设置分账明细信息
    public function setRoyaltyParameters($account, $amount)
    {
        $this->royaltyParameters = [[
            'royalty_type' => 'transfer',
            'trans_in_type'=>'loginName',
            'trans_in' => $account,
            'amount'=>$amount,
            'desc'=>'分账给商户'
        ]];
    }

    //收款方信息
    public function setPayeeInfo($account, $name)
    {
        $this->payeeInfo = [
            'identity' => $account,
            'name' => $name,
            'identity_type' => 'ALIPAY_LOGON_ID'
        ];
    }
    /**
     * 解除绑定分账关系接口
     */
    public function unbindRelation($account,$name){
        //请求参数
        $requestConfigs = array(
            'out_request_no' => $this->outReqNo,
            'receiver_list' => [
                'type'=>'loginName',
                'account'=>$account,
                'bind_login_name'=>$account,
                'login_name'=>$account,
                'name'=>$name,
                'memo'=>'分账给合作伙伴'
            ],
        );
        //公共参数
        $commonConfigs = array(
            'app_id' => $this->appId,
            'method' => 'alipay.trade.royalty.relation.unbind', //接口名称
            'format' => 'JSON',
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_cert_sn' => $this->getAppCertSN(config('setting.ali_pay_app_public')),
            'alipay_root_cert_sn' => '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6',//$this->getRootCertSN($this->alipayRootContent),
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        $result = httpRequest('https://openapi.alipay.com/gateway.do?charset='.$this->charset, 'POST', $commonConfigs);
        $result = json_decode($result, true);
        $resultName = str_replace(".", "_", 'alipay.trade.royalty.relation.unbind') . "_response";
        $response = $result[$resultName];
        return $response;
    }
    /**
     * 绑定分账关系接口
     */
    public function bindRelation($account,$name){
        //请求参数
        $requestConfigs = array(
            'out_request_no' => $this->outReqNo,
            'receiver_list' => [
                'type'=>'loginName',
                'account'=>$account,
                'bind_login_name'=>$account,
                'login_name'=>$account,
                'name'=>$name,
                'memo'=>'分账给合作伙伴'
            ],
        );
        //公共参数
        $commonConfigs = array(
            'app_id' => $this->appId,
            'method' => 'alipay.trade.royalty.relation.bind', //接口名称
            'format' => 'JSON',
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_cert_sn' => $this->getAppCertSN(config('setting.ali_pay_app_public')),
            'alipay_root_cert_sn' => '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6',//$this->getRootCertSN($this->alipayRootContent),
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        $result = httpRequest('https://openapi.alipay.com/gateway.do?charset='.$this->charset, 'POST', $commonConfigs);
        $result = json_decode($result, true);
        $resultName = str_replace(".", "_", 'alipay.trade.royalty.relation.bind') . "_response";
        $response = $result[$resultName];
        return $response;
    }
    /**
     * 分账结算接口
     */
    public function settlePay(){
        //请求参数
        $requestConfigs = array(
            'out_request_no' => $this->outReqNo,
            'trade_no' => $this->tradeNo,
            'royalty_parameters' => $this->royaltyParameters,//业务场景
        );
        //公共参数
        $commonConfigs = array(
            'app_id' => $this->appId,
            'method' => $this->settleProName, //接口名称
            'format' => 'JSON',
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_cert_sn' => $this->getAppCertSN(config('setting.ali_pay_app_public')),
            'alipay_root_cert_sn' => '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6',//$this->getRootCertSN($this->alipayRootContent),
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        $result = httpRequest('https://openapi.alipay.com/gateway.do?charset='.$this->charset, 'POST', $commonConfigs);
        $result = json_decode($result, true);
        $resultName = str_replace(".", "_", $this->settleProName) . "_response";
        $response = $result[$resultName];
        return $response;
    }
    /**
     * 单笔转账付款
     */
    public function transPay()
    {
        //请求参数
        $requestConfigs = array(
            'out_biz_no' => $this->outBizNo,
            'trans_amount' => $this->transAmount,//金额
            'biz_scene' => 'DIRECT_TRANSFER',//业务场景
            'product_code' => 'TRANS_ACCOUNT_NO_PWD', //销售产品码
            'order_title' => $this->orderTitle,//转账业务的标题
            'payee_info' => $this->payeeInfo,//收款方信息
            'remark' => $this->remark
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => $this->transProName, //接口名称
            'format' => 'JSON',
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_cert_sn' => $this->getAppCertSN(config('setting.ali_pay_app_public')),
            'alipay_root_cert_sn' => '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6',//$this->getRootCertSN($this->alipayRootContent),
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        $result = httpRequest('https://openapi.alipay.com/gateway.do?charset='.$this->charset, 'POST', $commonConfigs);
        $result = json_decode($result, true);
        $resultName = str_replace(".", "_", $this->transProName) . "_response";
        $response = $result[$resultName];
        return $response;
    }

    /**
     * 获取应用证书序列号
     */
    public function getAppCertSN($certContent)
    {
        $ssl = openssl_x509_parse($certContent);
        $SN = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 获取支付宝根证书序列号
     */
    public function getRootCertSN($certContent)
    {
        $this->alipayRootContent = $certContent;
        $array = explode("-----END CERTIFICATE-----", $certContent);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumberHex']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5(array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5(array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    /**
     * 0x转高精度数字
     * @param $hex
     * @return int|string
     */
    function hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    protected function array2string($array)
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }

    /**
     * 发起退款
     */
    public function refund()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no' => $this->outTradeNo,
            'out_request_no' => $this->outRequestNo,//退款订单号
            'refund_amount' => $this->refundFee,//退款金额
            'refund_reason' => $this->refundReason, //退款原因
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => $this->refundProName, //接口名称
            'format' => 'JSON',
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        $result = httpRequest('https://openapi.alipay.com/gateway.do', 'POST', $commonConfigs, [], false, true);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 发起订单
     * @return string
     */
    public function doPay()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no' => $this->outTradeNo,
            'product_code' => $this->payProCode,//  //QUICK_WAP_WAY  FAST_INSTANT_TRADE_PAY
            'total_amount' => $this->totalFee, //单位 元
            'subject' => $this->orderName,  //订单标题
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => $this->payProName, //            //接口名称
            'format' => 'JSON',
            'return_url' => $this->returnUrl,
            'charset' => $this->charset,
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $this->notifyUrl,
            'app_cert_sn' => $this->getAppCertSN(config('setting.ali_pay_app_public')),
            'alipay_root_cert_sn' => '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6',
            'biz_content' => json_encode($requestConfigs),
        );
        ksort($commonConfigs);
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        foreach ($commonConfigs as &$value) {
            $value = $this->characet($value, $commonConfigs['charset']);
        }
        return 'https://openapi.alipay.com/gateway.do?' . http_build_query($commonConfigs);
        //return $this->buildRequestForm($commonConfigs);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp array 请求参数数组
     * @return string 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp)
    {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://openapi.alipay.com/gateway.do?charset=" . $this->charset . "' method='POST'>";
        while (list ($key, $val) = each($para_temp)) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'", "&apos;", $val);
                $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }

    public function generateSign($params, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = $this->appPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION, '5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }

    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
}
