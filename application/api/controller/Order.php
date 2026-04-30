<?php

namespace app\api\controller;

use app\common\lib\Epay;
use app\common\lib\Toutiao;
use app\common\lib\Xunhu;
use app\common\model\AdminSite;
use app\common\model\Agent;
use app\common\model\Bill;
use app\common\model\GroupsLog;
use app\common\model\{
    Order as OrderModel,
    Resource,
    User,
    Admin,
    Svip,
    SvipPrivilege
};
use app\api\validate\Check;
use app\common\model\Privilege;
use app\common\model\ResourceKammi;
use app\common\model\SiteOrder;
use app\common\model\Spread;
use app\common\model\UserThird;
use app\common\model\Validate;
use Naixiaoxin\ThinkWechat\Facade;
use app\common\model\Kammi;
use think\Db;
use app\common\model\UserResource;
use app\common\model\AdminBill;
use think\Exception;

class Order extends Common
{
    /**
     * 社群入群付费
     */
    public function buyGroupIn()
    {
        try {
            if (request()->isPost()) {
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('Order.buyGroupIn')->check($data)) {
                    $this->error($validate->getError());
                }
                $uid = request()->uid;
                $userinfo = User::where('id', $uid)->find();
                if (!$userinfo) {
                    $this->error('请登录后再操作');
                }
                $groups = \app\common\model\Groups::where('id', $data['id'])->find();
                if (!$groups) {
                    $this->error('社群不存在');
                }
                if ($groups->status == 0) {
                    $this->error('社群已下线');
                }
                $arr['ordno'] = date('YmdHis') . mt_rand(10000, 99999);
                $arr['money'] = $groups->price;
                $arr['admin_id'] = $userinfo->admin_id;//代理
                $arr['pid'] = $userinfo->pid;//上级UID
                $arr['uid'] = $uid;
                $arr['nickname'] = $userinfo->nickname;
                $arr['type'] = 3;
                $arr['rid'] = $data['id'];//社群ID
                $arr['cdkey'] = empty($data['cdkey']) ? '' : $data['cdkey'];//卡密
                Db::startTrans();
                switch ($data['pay_type']) {
                    case 1:#微信小程序
                        #默认微信小程序支付
                        $arr['pay_type'] = 1;
                        if (!(new OrderModel)->save($arr)) {
                            Db::rollback();
                            $this->error('购买失败');
                        }
                        $openid = (new UserThird())->getFieldVal(1, $userinfo->id, 'openid');
                        if (empty($openid)) {
                            Db::rollback();
                            $this->error('购买失败');
                        }
                        $order = [
                            'body' => '查看社群',
                            'out_trade_no' => $arr['ordno'],
                            'total_fee' => $arr['money'] * 100,
                            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                            'openid' => $openid,
                        ];
                        $payment = Facade::payment(); // 微信支付
                        $result = $payment->order->unify($order);
                        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
                            $jssdk = $payment->jssdk;
                            $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                            Db::commit();
                            $this->success('success', $config);
                        } else {
                            Db::rollback();
                            $this->error('下单失败');
                        }
                        break;
                    case 2:#H5端支付
                        if ($data['pay_plat'] == 1) {
                            $arr['pay_type'] = 1;
                            if (!(new OrderModel)->save($arr)) {
                                Db::rollback();
                                $this->error('购买失败');
                            }
                            $wechat_payType = config('setting.wechat_pay_type');
                            switch (intval($wechat_payType)) {
                                case 1:#微信官方支付
                                    $order = [
                                        'body' => '查看社群',
                                        'out_trade_no' => $arr['ordno'],
                                        'total_fee' => $arr['money'] * 100,
                                        'trade_type' => 'MWEB',
                                        'scene_info' => json_encode(
                                            [
                                                "h5_info" => [
                                                    'type' => 'h5_info',
                                                    'wap_url' => config('setting.account_domain'),
                                                    'wap_name' => '购买会员'
                                                ]
                                            ]
                                        )
                                    ];
                                    $payment = Facade::payment('official_account'); // 微信支付
                                    $result = $payment->order->unify($order);
                                    if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                                        Db::commit();
                                        $this->success('success', ['pay_url' => $result['mweb_url'], 'ordno' => $arr['ordno']]);
                                    } else {
                                        Db::rollback();
                                        $this->error('下单失败');
                                    }
                                    break;
                                case 2:#虎皮椒微信支付
                                    $payment = new Xunhu();
                                    $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                    $return_url = $data['return_url'];
                                    $callback_url = $data['return_url'];
                                    list($result, $response) = $payment->createPay(
                                        'wechat',
                                        'wap',
                                        $arr['ordno'],
                                        $arr['money'],
                                        '查看社群',
                                        $notify_url,
                                        $return_url,
                                        $callback_url
                                    );
                                    if (!$result) {
                                        Db::rollback();
                                        $this->error($response);
                                    }
                                    Db::commit();
                                    $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                    break;
                            }
                        } else {
                            $arr['pay_type'] = 2;
                            if (!(new OrderModel)->save($arr)) {
                                Db::rollback();
                                $this->error('购买失败');
                            }
                            $alipay_payType = config('setting.alipay_pay_type');
                            switch (intval($alipay_payType)) {
                                case 1:#支付宝官方支付
                                    $aliPay = new \alipay\wap();
                                    $aliPay->setAppid(config('setting.ali_pay_appid'));
                                    $aliPay->setReturnUrl($data['return_url']);
                                    $aliPay->setNotifyUrl(config('setting.ali_pay_notify'));
                                    $aliPay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                    $aliPay->setTotalFee($arr['money']);
                                    $aliPay->setOutTradeNo($arr['ordno']);
                                    $aliPay->setOrderName('查看社群');
                                    $result = $aliPay->doPay();
                                    Db::commit();
                                    $this->success('success', ['pay_url' => $result, 'ordno' => $arr['ordno']]);
                                    break;
                                case 2:#虎皮椒支付宝支付
                                    $payment = new Xunhu();
                                    $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                    $return_url = $data['return_url'];
                                    $callback_url = $data['return_url'];
                                    list($result, $response) = $payment->createPay(
                                        'alipay',
                                        'wap',
                                        $arr['ordno'],
                                        $arr['money'],
                                        '查看社群',
                                        $notify_url,
                                        $return_url,
                                        $callback_url
                                    );
                                    if (!$result) {
                                        Db::rollback();
                                        $this->error($response);
                                    }
                                    Db::commit();
                                    $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                    break;
                            }
                        }
                        break;
                    case 3:#卡密
                        //判断合伙人
                        if (!empty($userinfo->pid)) {
                            $pinfo = User::where(['id' => $userinfo->pid, 'status' => 1])->find();
                            if ($pinfo && (time() < $pinfo->exp_time)) {
                                //获取邀请人特权
                                $rule = Privilege::handleUserAuth($pinfo->vid, '', 5);
                                if ($rule) {
                                    //合伙人设置
                                    $arr['hr_money'] = bcmul($rule, $arr['money'], 2);
                                }
                            }
                        }
                        #cdkey购买处理
                        if (empty($arr['cdkey'])) {
                            $this->error('卡密不存在或已经使用');
                        }
                        $kammi = Kammi::where('cdkey', $arr['cdkey'])->find();
                        if (!$kammi) {
                            $this->error('卡密不存在或已经使用');
                        }
                        if ($kammi['status'] !== 0) {
                            $this->error('卡密已经使用');
                        }
                        #判断卡密是否指定资源
                        if ($arr['money'] != $kammi['money']) {
                            $this->error('卡密价格错误');
                        }
                        #写入订单数据
                        $arr['trade_id'] = $arr['cdkey'];
                        $arr['status'] = 1;
                        $arr['pay_type'] = 3;
                        $order = OrderModel::create($arr, true);
                        if (!$order) {
                            $this->error('购买失败');
                        }
                        #写入购买记录
                        $arr1 = [
                            'group_id' => $order->rid,
                            'uid' => $order->uid,
                            'type' => 1,
                            'admin_id' => $order->admin_id
                        ];
                        $res1 = GroupsLog::create($arr1);
                        if (!$res1) {
                            Db::rollback();
                            $this->error('操作失败，用户使用写入失败');
                        }
                        #更新卡密使用状态
                        $kammi->uid = $order->uid;
                        $kammi->status = 1;
                        if (!$kammi->save()) {
                            Db::rollback();
                            $this->error('操作失败，更新失败');
                        }
                        #代理收益
                        list($res, $info) = $this->agent($order->ordno);
                        if (!$res) {
                            Db::rollback();
                            $this->error($info);
                        }
                        #更新销量
                        $groups->views = ['inc', 1];
                        $groups->save();
                        Db::commit();
                        $this->success('success');
                        break;
                    case 4:#抖音小程序
                        $arr['pay_type'] = 4;
                        if (!(new OrderModel)->save($arr)) {
                            Db::rollback();
                            $this->error('购买失败');
                        }
                        $order = [
                            'out_order_no' => $arr['ordno'],
                            'subject' => '查看社群',
                            'body' => '查看社群信息',
                            'total_amount' => $arr['money'] * 100
                        ];
                        $toutiao = new Toutiao(); // 微信支付
                        $result = $toutiao->createOrder($order);
                        if (isset($result['order_id'])) {
                            Db::commit();
                            $this->success('success', $result);
                        } else {
                            Db::rollback();
                            $this->error('下单失败');
                        }
                        break;
                    case 5:#微信公众号
                        $arr['pay_type'] = 5;
                        if (!(new OrderModel)->save($arr)) {
                            Db::rollback();
                            $this->error('购买失败');
                        }
                        $wechat_payType = config('setting.wechat_pay_type');
                        switch (intval($wechat_payType)) {
                            case 1:#官方微信支付
                                $openid = (new UserThird())->getFieldVal(2, $userinfo->id, 'openid');
                                if (empty($openid)) {
                                    Db::rollback();
                                    $this->error('购买失败');
                                }
                                $order = [
                                    'body' => '查看社群',
                                    'out_trade_no' => $arr['ordno'],
                                    'total_fee' => $arr['money'] * 100,
                                    'trade_type' => 'JSAPI',
                                    'openid' => $openid,
                                ];
                                $payment = Facade::payment('official_account'); // 微信支付
                                $result = $payment->order->unify($order);
                                if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                    $jssdk = $payment->jssdk;
                                    $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                                    Db::commit();
                                    $this->success('success', $config);
                                } else {
                                    Db::rollback();
                                    $this->error('下单失败');
                                }
                                break;
                            case 2:#虎皮椒微信支付
                                $payment = new Xunhu();
                                $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                $return_url = $data['url'];
                                $callback_url = $data['url'];
                                list($result, $response) = $payment->createPay(
                                    'wechat',
                                    'wap',
                                    $arr['ordno'],
                                    $arr['money'],
                                    '查看社群',
                                    $notify_url,
                                    $return_url,
                                    $callback_url
                                );
                                if (!$result) {
                                    Db::rollback();
                                    $this->error($response);
                                }
                                Db::commit();
                                $this->success('success', $response);
                                break;
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 微信小程序支付
     */
    public function createPayOrder()
    {
        try {
            if ($this->request->isPost()) {
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('Order.createOrder')->check($data)) {
                    $this->error($validate->getError());
                }
                $uid = request()->uid;
                $userinfo = User::where('id', $uid)->find();
                if (empty($userinfo)) {
                    $this->error('用户不存在');
                }
                list($res, $msg) = $this->checkThrottle($uid, $data['id']);
                if (!$res) {
                    $this->error($msg);
                }
                if ($data['type'] == 1) {
                    $spread = Spread::where('id', $data['id'])->find();
                    if (!$spread) {
                        $this->error('资源不存在');
                    }
                    if ($spread->status == 0) {
                        $this->error('资源已下架');
                    }
                    if ($spread->price == 0) {
                        $this->error('资源价格不能为零');
                    }
                    #判断卡密库存
                    if($spread->type==6){
                        $nums=ResourceKammi::where(['rid'=>$spread->rid,'status'=>0])->count();
                        if($nums<1){
                            $this->error('卡密库存不足，请联系客服');
                        }
                    }
                    $dis_price = Svip::getDiscountPrice($userinfo->vid, $spread->price);
                    $arr['money'] = $dis_price > 0 ? $dis_price : $spread->price;
                    $arr['rid'] = $spread->id;
                    $arr['spread_rid'] = $spread->rid;
                    $arr['spread_type'] = $spread->type;
                    $arr['body'] = $spread->title;
                    $arr['item_id'] = $spread->id;
                    $arr['item_title'] = $spread->title;
                    $arr['item_thumb'] = $spread->thumb;
                    //判断分销
                    if ($spread->is_fenxiao) {
                        $commission = (new User())->computeCommission(1, $arr['money'], $userinfo->pid, $spread);
                        $arr['hr_money'] = $commission['hr_money'];
                        $arr['sr_money'] = $commission['sr_money'];
                    }
                } else {
                    $svip = Svip::where('id', $data['id'])->find();
                    if (!$svip) {
                        $this->error('会员套餐不存在');
                    }
                    #校验验证码
                    list($res, $msg) = $this->checkCodeAndUser($data);
                    if (!$res) {
                        $this->error($msg);
                    }
                    $arr['money'] = $svip->price;
                    $arr['vid'] = $svip->id;
                    $arr['body'] = $svip->name;
                    $arr['item_id'] = $svip->id;
                    $arr['item_title'] = $svip->name;
                    $arr['item_thumb'] = config('setting.web_logo');
                    if (!empty($userinfo->pid)) {
                        $commission = (new User())->computeCommission(2, $arr['money'], $userinfo->pid, null);
                        $arr['hr_money'] = $commission['hr_money'];
                        $arr['sr_money'] = $commission['sr_money'];
                    }
                }
                Db::startTrans();
                $wechat_payType = intval(config('setting.wechat_pay_type'));
                $alipay_payType = intval(config('setting.alipay_pay_type'));
                $wxmini_payType = intval(config('setting.wxnini_pay_type'));
                $arr['ordno'] = date('YmdHis') . mt_rand(10000, 99999);
                $arr['admin_id'] = $userinfo->admin_id;
                $arr['pid'] = $userinfo->pid;
                $arr['uid'] = $uid;
                $arr['nickname'] = $userinfo->nickname;
                $arr['type'] = $data['type'];
                $arr['pay_type'] = $data['pay_type'];
                $arr['platform'] = $data['platform'];
                if (!(new OrderModel)->save($arr)) {
                    Db::rollback();
                    $this->error('购买失败');
                }
                #创建代理分站订单
                if (!empty($data['agreen'])) {
                    $agent = $data;
                    $agent['ordno'] = $arr['ordno'];
                    $agent['uid'] = $uid;
                    $agent['money'] = $arr['money'];
                    $agent['svip_id'] = $arr['vid'];
                    $agent['admin_id'] = $arr['admin_id'];
                    list($res, $msg) = SiteOrder::createSiteOrder($agent);
                    if (!$res) {
                        Db::rollback();
                        $this->error($msg);
                    }
                }
                switch ($data['pay_type']) {
                    case 1:#微信支付
                        switch (strtoupper($data['platform'])) {
                            case 'WECHAT_MINI':#微信支付小程序
                                switch ($wxmini_payType) {
                                    case 0:#微信支付商户号
                                        $openid = (new UserThird())->getFieldVal(1, $userinfo->id, 'openid');
                                        if (empty($openid)) {
                                            Db::rollback();
                                            $this->error('购买失败');
                                        }
                                        $order = [
                                            'body' => $arr['body'],
                                            'out_trade_no' => $arr['ordno'],
                                            'total_fee' => $arr['money'] * 100,
                                            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                                            'openid' => $openid,
                                        ];
                                        $payment = Facade::payment(); // 微信支付
                                        $result = $payment->order->unify($order);
                                        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                            $jssdk = $payment->jssdk;
                                            $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                                            Db::commit();
                                            $this->success('success', $config);
                                        } else {
                                            Db::rollback();
                                            $this->error($result['return_msg']);
                                        }
                                        break;
                                    case 1:#虎皮椒微信支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        list($result, $response) = $payment->createWechatMiniPay('wxmini', 'JSAPI', $arr['ordno'], $arr['money'], $arr['body'], $notify_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['data' => $response['data'],'appid' => $response['appid'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                            case 'WECHAT_OFFICIAL':#微信支付公众号
                                switch ($wechat_payType) {
                                    case 1:#官方微信支付
                                        $openid = (new UserThird())->getFieldVal(2, $userinfo->id, 'openid');
                                        $from_id = $this->request->param('from_id');
                                        $admin_id = (new Admin())->getDefaultAdminId();
                                        if (empty($openid) || ($from_id !== $admin_id)) {
                                            #重新获取用户OPENID
                                            $appid = config('setting.account_appid');
                                            $urlback = urlencode(config('setting.account_domain') . '/#/custom/prompt/transfer?url=' . $data['return_url']);
                                            $state = $arr['ordno'];
                                            $api_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
                                            $url = $api_url . '?appid=' . $appid . '&redirect_uri=' . $urlback . '&response_type=code&scope=snsapi_base&state=' . $state . '#wechat_redirect';
                                            Db::commit();
                                            $this->error('获取用户openid', ['url' => $url], 103);
                                        }
                                        $order = [
                                            'body' => $arr['body'],
                                            'out_trade_no' => $arr['ordno'],
                                            'total_fee' => $arr['money'] * 100,
                                            'trade_type' => 'JSAPI',
                                            'openid' => $openid,
                                        ];
                                        $payment = Facade::payment('official_account');
                                        $result = $payment->order->unify($order);
                                        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                            $jssdk = $payment->jssdk;
                                            $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                                            Db::commit();
                                            $this->success('success', $config);
                                        } else {
                                            Db::rollback();
                                            $this->error($result['return_msg']);
                                        }
                                        break;
                                    case 2:#虎皮椒微信支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('wechat', 'wap',
                                            $arr['ordno'],
                                            $arr['money'],
                                            $arr['body'],
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', $response);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('wechat', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                            case 'WECHAT_H5':#微信支付H5
                                switch ($wechat_payType) {
                                    case 1:#微信官方支付
                                        $order = [
                                            'body' => $arr['body'],
                                            'out_trade_no' => $arr['ordno'],
                                            'total_fee' => $arr['money'] * 100,
                                            'trade_type' => 'MWEB',
                                            'scene_info' => json_encode(
                                                [
                                                    "h5_info" => [
                                                        'type' => 'h5_info',
                                                        'wap_url' => config('setting.account_domain'),
                                                        'wap_name' => $arr['body']
                                                    ]
                                                ]
                                            )
                                        ];
                                        $payment = Facade::payment('official_account'); // 微信支付
                                        $result = $payment->order->unify($order);
                                        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                            Db::commit();
                                            $this->success('success', ['pay_url' => $result['mweb_url'], 'ordno' => $arr['ordno']]);
                                        } else {
                                            Db::rollback();
                                            $this->error($result['return_msg']);
                                        }
                                        break;
                                    case 2:#虎皮椒微信支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('wechat', 'wap', $arr['ordno'], $arr['money'],
                                            '购买资源',
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('wechat', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                            case 'WECHAT_PC':#微信扫码支付
                                switch ($wechat_payType) {
                                    case 1:#微信官方支付
                                        $order = [
                                            'trade_type' => 'NATIVE',
                                            'product_id' => $data['id'],
                                            'total_fee' => $arr['money'] * 100,
                                            'out_trade_no' => $arr['ordno'],
                                            'body' => $arr['body'],
                                            'notify_url' => config('setting.wxpay_notify')
                                        ];
                                        $payment = Facade::payment('official_account'); // 微信支付
                                        $result = $payment->order->unify($order);
                                        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                            Db::commit();
                                            $this->success('success', ['pay_url' => $result['code_url'], 'money' => $arr['money'], 'ordno' => $arr['ordno']], 103);
                                        } else {
                                            Db::rollback();
                                            $this->error($result['return_msg']);
                                        }
                                        break;
                                    case 2:#虎皮椒微信支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('wechat', 'wap', $arr['ordno'], $arr['money'],
                                            $arr['body'],
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['pay_url' => $response['url_qrcode'], 'money' => $arr['money'],'ordno' => $arr['ordno']],103);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('wechat', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                        }
                        break;
                    case 2:#支付宝支付
                        switch (strtoupper($data['platform'])) {
                            case 'ALIPAY_WX':#微信内支付宝支付
                                switch ($alipay_payType) {
                                    case 1:#支付宝官方支付
                                        Db::commit();
                                        $this->success('success', ['ordno' => $arr['ordno']]);
                                        break;
                                    case 2:#虎皮椒支付宝支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('alipay', 'wap', $arr['ordno'], $arr['money'], $arr['body'],
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('alipay', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                            case 'ALIPAY_H5':#手机支付
                                switch ($alipay_payType) {
                                    case 1:#支付宝官方支付
                                        $aliPay = new \alipay\wap();
                                        $aliPay->setAppid(config('setting.ali_pay_appid'));
                                        $aliPay->setReturnUrl($data['return_url']);
                                        $aliPay->setNotifyUrl(config('setting.ali_pay_notify'));
                                        $aliPay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                        $aliPay->setTotalFee($arr['money']);
                                        $aliPay->setOutTradeNo($arr['ordno']);
                                        $aliPay->setOrderName($arr['body']);
                                        $result = $aliPay->doPay();
                                        Db::commit();
                                        $this->success('success', ['pay_url' => $result, 'ordno' => $arr['ordno']]);
                                        break;
                                    case 2:#虎皮椒支付宝支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('alipay', 'wap', $arr['ordno'], $arr['money'], $arr['body'],
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('alipay', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                            case 'ALIPAY_PC':#网站支付
                                switch ($alipay_payType) {
                                    case 1:#支付宝官方支付
                                        $aliPay = new \alipay\wap();
                                        $aliPay->setAppid(config('setting.ali_pay_appid'));
                                        $aliPay->setReturnUrl($data['return_url']);
                                        $aliPay->setNotifyUrl(config('setting.ali_pay_notify'));
                                        $aliPay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                        $aliPay->setTotalFee($arr['money']);
                                        $aliPay->setOutTradeNo($arr['ordno']);
                                        $aliPay->setOrderName($arr['body']);
                                        $aliPay->setProName('alipay.trade.page.pay');
                                        $aliPay->setProCode('FAST_INSTANT_TRADE_PAY');
                                        $result = $aliPay->doPay();
                                        Db::commit();
                                        $this->success('success', ['pay_url' => $result, 'ordno' => $arr['ordno']]);
                                        break;
                                    case 2:#虎皮椒支付宝支付
                                        $payment = new Xunhu();
                                        $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                        $return_url = $data['return_url'];
                                        $callback_url = $data['return_url'];
                                        list($result, $response) = $payment->createPay('alipay', 'wap', $arr['ordno'], $arr['money'], $arr['body'],
                                            $notify_url,
                                            $return_url,
                                            $callback_url
                                        );
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['url'], 'ordno' => $arr['ordno']]);
                                        break;
                                    case 3:#易支付
                                        $payment = new Epay();
                                        $notify_url = $this->request->domain() . '/index/Notify/epayNotify';
                                        $return_url = $data['return_url'];
                                        list($result, $response) =  $payment->createPay('alipay', $arr['ordno'], $arr['money'], $arr['body'], $notify_url, $return_url);
                                        if (!$result) {
                                            Db::rollback();
                                            $this->error($response);
                                        }
                                        Db::commit();
                                        $this->success('success', ['url' => $response['payurl'], 'ordno' => $arr['ordno']]);
                                        break;
                                }
                                break;
                        }
                        break;
                    case 3:#卡密支付
                        $cdkeyData = array_merge($data, $arr);
                        list($res, $msg) = $this->handleKammi($cdkeyData);
                        if (!$res) {
                            Db::rollback();
                            $this->error($msg);
                        }
                        #更新销量
                        if ($data['type'] == 1) {
                            $spread->sales = ['inc', 1];
                            $spread->save();
                        }
                        Db::commit();
                        $this->success('success');
                        break;
                    case 4:#抖音支付
                        $order = [
                            'skuList' => [[
                                'skuId'=>(string)$arr['item_id'],
                                'price'=>intval($arr['money']*100),
                                'quantity'=>1,
                                'title'=>$arr['item_title'],
                                'imageList'=>[$arr['item_thumb']],
                                'type'=>301,
                                'tagGroupId'=>'tag_group_7272625659888058380'
                            ]],
                            'outOrderNo'=>$arr['ordno'],
                            'totalAmount' => intval($arr['money']*100),
                            'payNotifyUrl'=> config('setting.tt_notify'),
                            'orderEntrySchema'=>[
                                'path'=>'pages/my/orderDetail',
                                'params'=>'{"ordno":"'.$arr['ordno'].'"}'
                            ],
                        ];
                        $order=json_encode($order);
                        $toutiao = new Toutiao(); // 抖音支付
                        list($res,$result) = $toutiao->getByteAuth($order);
                        if ($res) {
                            Db::commit();
                            $this->success('success', ['tt_data'=>$order,'byte_auth'=>$result]);
                        } else {
                            Db::rollback();
                            $this->error($result);
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 检测订单多次重复提交
     */
    protected function checkThrottle($uid, $id)
    {
        $key = md5($uid . '_' . $id);
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.port'));
        if (!empty(config('redis.auth'))) {
            $redis->auth(config('redis.auth'));
        }
        $passed = $redis->exists($key);
        if ($passed) {
            $redis->incr($key);
            $count = $redis->get($key);
            if ($count > config('redis.order')) {
                return [false, '请勿重复提交'];
            }
        } else {
            $redis->incr($key);
            $redis->pExpire($key, 1000 * 60 * 60 * 24);
        }
        return [true, 'allow'];
    }

    /**
     * 微信公众号根据订单调起支付宝支付
     */
    public function handleAlipay()
    {
        try {
            if ($this->request->isPost()) {
                $data = input('param.');
                $validate = new Check;
                if (!$validate->scene('Order.handleAlipay')->check($data)) {
                    $this->error($validate->getError());
                }
                $ordno = $data['ordno'];
                $order = \app\common\model\Order::where('ordno', $ordno)->find();
                if (!$order) {
                    $this->error('支付失败');
                }
                $spread_id = $order->rid;
                $spread_type = 0;
                $agent_vip = 0;#是否代理套餐
                $spread = Spread::where('id', $order->rid)->find();
                if (!empty($spread)) {
                    $spread_id = $spread->id;
                    $spread_type = $spread->type;
                }
                $body = '购买资源';
                if ($order->type == 2) {
                    $svip = Svip::where(['id' => $order->vid, 'type' => 1])->find();
                    if (!empty($svip)) {
                        $agent_vip = 1;
                    }
                    $body = '购买会员';
                }
                $aliPay = new \alipay\wap();
                $aliPay->setAppid(config('setting.ali_pay_appid'));
                $aliPay->setReturnUrl($data['return_url']);
                $aliPay->setNotifyUrl(config('setting.ali_pay_notify'));
                $aliPay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                $aliPay->setTotalFee($order->money);
                $aliPay->setOutTradeNo($ordno);
                $aliPay->setOrderName($body);
                $result = $aliPay->doPay();
                $this->success('success', ['pay_url' => $result, 'ordno' => $ordno, 'spread_id' => $spread_id, 'spread_type' => $spread_type, 'agent_vip' => $agent_vip]);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 微信公众号根据订单调起公众号微信支付
     */
    public function handleOfficial()
    {
        try {
            if ($this->request->isPost()) {
                $data = input('param.');
                $validate = new Check;
                if (!$validate->scene('Login.oauth')->check($data)) {
                    $this->error($validate->getError());
                }
                $app = Facade::officialAccount();
                $acc_token = $app->oauth->getAccessToken($data['code']);
                $user = $app->oauth->user($acc_token);
                $openid = $user->getId();// 对应微信的 OPENID
                $ordno = $data['ordno'];
                $order = \app\common\model\Order::where('ordno', $ordno)->find();
                if (!$order) {
                    $this->error('支付失败');
                }
                $spread_id = $order->rid;
                $spread_type = 0;
                $agent_vip = 0;#是否代理套餐
                $spread = Spread::where('id', $order->rid)->find();
                if (!empty($spread)) {
                    $spread_id = $spread->id;
                    $spread_type = $spread->type;
                }
                $body = '购买资源';
                if ($order->type == 2) {
                    $svip = Svip::where(['id' => $order->vid, 'type' => 1])->find();
                    if (!empty($svip)) {
                        $agent_vip = 1;
                    }
                    $body = '购买会员';
                }
                $data = [
                    'body' => $body,
                    'out_trade_no' => $ordno,
                    'total_fee' => $order->money * 100,
                    'trade_type' => 'JSAPI',
                    'openid' => $openid,
                ];
                $payment = Facade::payment('official_account');
                $result = $payment->order->unify($data);
                if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                    $jssdk = $payment->jssdk;
                    $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                    $config['order'] = ['rid' => $spread_id, 'type' => $spread_type, 'agent_vip' => $agent_vip];
                    Db::commit();
                    $this->success('success', $config);
                } else {
                    Db::rollback();
                    $this->error($result['return_msg']);
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 查询H5支付订单
     */
    public function queryOrder()
    {
        $data = input('param.');
        $validate = new Check;
        if (!$validate->scene('Order.queryOrder')->check($data)) {
            $this->error($validate->getError());
        }
        $uid = request()->uid;
        $order = \app\common\model\Order::where(['ordno' => $data['ordno'], 'uid' => $uid])->find();
        if (!$order) {
            $this->error('查询失败，订单不存在！');
        }
        if ($order->status == 1) {
            $this->success('订单支付成功！');
        }
        $this->error('订单未支付！', [], 105);
    }

    /**
     * 校验验证码及用户名
     * @param $data array
     * @return array 数组
     */
    protected function checkCodeAndUser($data)
    {
        #验证分站验证码
        if (!empty($data['agreen'])) {
            if (empty($data['prefix'])) {
                return [false, '请输入二级域名前缀'];
            }
            if (empty($data['username'])) {
                return [false, '请输入分站管理用户名'];
            }
            if (empty($data['password'])) {
                return [false, '请输入分站管理登录密码'];
            }
            if (empty($data['suffix'])) {
                return [false, '请选择根域名'];
            }
            if (empty($data['webname'])) {
                return [false, '请输入分站名称'];
            }
            #判断域名前缀是否重复
            $sites = AdminSite::where(['sub_prefix' => $data['prefix'], 'base_domain' => $data['suffix']])->find();
            if ($sites) {
                return [false, '创建失败，分站域名前缀已经存在了'];
            }
            #判断用户名是否重复
            $users = Admin::where('username', $data['username'])->find();
            if ($users) {
                return [false, '创建失败，代理账号已经存在了'];
            }
        }
        return [true, 'success'];
    }

    /**
     * 卡密处理
     * @param $arr array 订单数据
     * @return array 数组
     */
    protected function handleKammi($arr)
    {
        try {
            if (!isset($arr['cdkey'])) {
                return [false, '卡密不存在或已经使用'];
            }
            $kammi = Kammi::where('cdkey', $arr['cdkey'])->find();
            if (!$kammi) {
                return [false, '卡密不存在或已经使用'];
            }
            if ($kammi['status'] !== 0) {
                return [false, '卡密已经使用'];
            }
            #判断卡密是否指定会员
            if (!empty($kammi['vid'])) {
                if ($kammi['vid'] != $arr['vid']) {
                    return [false, '卡密类型，此卡密无法使用'];
                }
            } else {
                if ($arr['money'] != $kammi['money']) {
                    return [false, '卡密价格错误'];
                }
            }
            #写入订单数据
            switch ($arr['type']) {
                case 1:#购买资源后处理
                    #todo 卡密多次购买
                    $work_id = 0;
                    if ($arr['spread_type'] == 6) {
                        $kam = ResourceKammi::where(['rid' => $arr['spread_rid'], 'uid' => 0])->find();
                        if (empty($kam)) {
                            return [false, '卡密库存不足，购买失败'];
                        }
                        $kam->uid = $arr['uid'];
                        $kam->utime = time();
                        $kamres = $kam->save();
                        if (!$kamres) {
                            return [false, '数据异常，购买失败'];
                        }
                        $work_id = $kam->id;
                    }
                    $arr1 = [
                        'rid' => $arr['rid'],
                        'uid' => $arr['uid'],
                        'work_id' => $work_id,
                        'type' => 1,
                        'admin_id' => $arr['admin_id']
                    ];
                    $res1 = UserResource::create($arr1);
                    if (!$res1) {
                        return [false, '操作失败，用户使用写入失败'];
                    }
                    break;
                case 2:#购买VIP后处理
                    $user = User::where('id', $arr['uid'])->find();
                    if (!$user) {
                        return [false, '操作失败，用户不存在'];
                    }
                    $vip = Svip::where('id', $arr['vid'])->find();
                    if (!$vip) {
                        return [false, '操作失败，SVIP等级不存在'];
                    }
                    $user->vid = $arr['vid'];
                    $exp_time = $user->exp_time;
                    if ($exp_time < time()) {
                        $exp_time = time();
                    }
                    $user->exp_time = $exp_time + ($vip->days * 86400);
                    if (!$user->save()) {
                        return [false, '操作失败，处理SVIP失败'];
                    }
                    #创建代理分站订单
                    if (!empty($arr['agreen'])) {
                        $agent = $arr;
                        $agent['mobile'] = $user->mobile;
                        $agent['svip_id'] = $arr['vid'];
                        list($res, $msg) = SiteOrder::createSiteOrder($agent, 1);
                        if (!$res) {
                            return [false, $msg];
                        }
                    }
                    break;
            }
            #更新卡密使用状态
            $kammi->uid = $arr['uid'];
            $kammi->status = 1;
            $kammi->utime = time();
            if (!$kammi->save()) {
                return [false, '操作失败，更新失败'];
            }
            #代理收益
            list($res, $info) = $this->agent($arr['ordno']);
            if (!$res) {
                return [false, $info];
            }
            return [true, 'success'];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 代理收益
     * @return [type] [description]
     */
    protected function agent($ordno)
    {
        #============================计算代理收益================================================
        $order = OrderModel::where('ordno', $ordno)->find();

        if ($order->pay_type !== 3 || config('setting.is_cdkey_income') == '1') {
            //计算用户分销
            if ($order->hr_money > 0 && $order->pid > 0) {
                list($res, $info) = Bill::money(1, $order->type, $order->hr_money, $order->pid, '一级推广佣金', $order->id);
                if (!$res) {
                    return [false, $info];
                }
            }
            if ($order->sr_money > 0 && $order->pid > 0) {
                $puser = User::where(['id' => $order->pid])->find();
                if ($order->sr_money > 0 && $puser->pid > 0) {
                    list($res, $info) = Bill::money(1, $order->type, $order->sr_money, $puser->pid, '二级推广佣金', $order->id);
                    if (!$res) {
                        return [false, $info];
                    }
                }
            }
        }
        //代理提成
        $is_kl = 0;
        $agent = Admin::where(['id' => $order->admin_id, 'status' => 1])->find();
        if (empty($agent)) {
            return [false, '所属分站不存在'];
        }
        $admin_id = $order->admin_id;
        if ($agent->admin_id > 0) {
            #扣量比例
            $take_num = $agent->take_num;
            if ($take_num > 0) {
                $count = (new \app\common\model\Order())->where(['admin_id' => $order->admin_id, 'status' => 1])->count();
                if ($count > 0 && ($count + 1) % $take_num == 0) {
                    $is_kl = 1;
                }
            }
        }
        //优化逻辑扣量
        if ($is_kl == 1) {
            //扣量逻辑
            if ($agent->admin_id == 0) {
                $admin_id = $agent->id;
            } else {
                $admin_id = (new Admin())->getDefaultAdminId();//todo 获取总代理即管理员ID
            }
        }
        //计算提成
        $min_take = $agent->min_take;
        $take_money = 0;
        #剩余金额
        $money = bcsub($order->money, ($order->hr_money + $order->sr_money), 2);
        #上级代理分成
        if ($min_take > 0 && $is_kl == 0 && $agent->admin_id > 0) {
            $take_money = bcdiv(bcmul($order->money, $min_take, 2), 100, 2);
            if ($take_money && $money > $take_money) {
                $money = bcsub($money, $take_money, 2);
            } else {
                doSyslog($order->money . '==' . $order->hr_money . '==' . $order->sr_money . '==' . $take_money . '佣金比例设置超过100%', 'handleOrder');
                $take_money = 0;
            }
        }
        #收益处理
        if ($order->pay_type !== 3 || config('setting.is_cdkey_income') == '1') {
            if ($money > 0 && $admin_id > 0) {
                #扣量处理
                if ($is_kl == 1) {
                    $remark = "【扣量订单】单号:{$order->ordno} 代理ID:" . $order->admin_id . " 代理名称:" . $agent->username;
                    $type = 4;
                } else {
                    if ($order->type == 1) {
                        $remark = '【资源销售】单号:' . $order->ordno;
                        $type = 1;
                    } else {
                        $remark = '【VIP销售】单号:' . $order->ordno;
                        $type = 3;
                    }
                }
                list($res, $info) = AdminBill::money(1, $type, $money, $admin_id, $remark, $order->id);
                if (!$res) {
                    return [false, $info];
                }
            }
        }
        #上级提成
        if ($order->pay_type !== 3 || config('setting.is_cdkey_income') == '1') {
            if ($take_money && $is_kl == 0 && $agent->admin_id > 0) {
                $remark = "【一级抽成】单号:{$order->ordno};提成比例{$agent->min_take}%;代理【{$agent->username}】ID:{$agent->id}";
                list($res, $info) = AdminBill::money(1, 3, $take_money, $agent->admin_id, $remark, $order->id);
                if (!$res) {
                    return [false, $info];
                }
            }
        }
        $order->pt_money = $money;
        $order->tc_money = $take_money;
        $order->is_kl = $is_kl;
        $order->status = 1;
        $res = $order->save(); // 保存订单
        if (!$res) {
            return [false, '订单更新失败'];
        }
        return [true, 'success'];
    }
}