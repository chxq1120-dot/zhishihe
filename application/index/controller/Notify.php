<?php

namespace app\index\controller;

use app\common\lib\Epay;
use app\common\lib\Toutiao;
use app\common\lib\Xunhu;
use app\common\model\Cash;
use app\common\model\UserThird;
use app\common\model\WxPay;
use app\common\model\{
    Bill,
    AdminBill,
    Order,
    UserResource,
    Admin,
    User,
    Svip
};
use app\common\model\Groups;
use app\common\model\RefundLog;
use app\common\model\ResourceKammi;
use app\common\model\SiteOrder;
use app\common\model\Spread;
use EasyWeChat\Core\Exception;
use EasyWeChat\Kernel\Exceptions\HttpException;
use Think\Db;
use Naixiaoxin\ThinkWechat\Facade;

class Notify
{
    public function epayNotify()
    {
        try {
            $params = input();
            if (empty($params)) {
                exit('fail');
            }
            $order = Order::where('ordno', $params['out_trade_no'])->find();
            if (!$order || $order->status == 1) {
                exit('success');
            }
            $pay_ment = 'wechat';
            if ($order->pay_type == 2) {
                $pay_ment = 'alipay';
            }
            $payment = new Epay();
            list($result, $message) = $payment->notify($params, $pay_ment);
            if ($result) {
                if ($message['trade_status'] === 'TRADE_SUCCESS') {//支付成功
                    $order->status = 1;
                    $order->ptime = time();
                    $order->utime = time();
                    $order->trade_id = $message['out_trade_no'];
                    list($result, $info) = $this->handleOrder($order);
                    if (!$result) {
                        doSyslog($info, 'epayNotify');
                        exit('fail');
                    }
                    exit('success');
                }
            }
            doSyslog($message, 'epayNotify');
            exit('fail');
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'epayNotify');
            exit('fail');
        }
    }

    //商家自动转账到零钱回调接口
    public function wxBatchNotify()
    {
        try {
            $payment = new WxPay();
            $params['body'] = input();
            if (empty($params['body'])) {
                return json(['code'=>503,'message'=>'无参数']);
            }
            $params['signature']=request()->header('Wechatpay-Signature');
            $params['timestamp']=request()->header('Wechatpay-Timestamp');
            $params['serial']=request()->header('Wechatpay-Serial');
            $params['nonce']=request()->header('Wechatpay-Nonce');
            list($res,$message)=$payment->notify($params);
            if(!$res){
                return json(['code'=>503,'message'=>$message]);
            }
            #转账成功
            $ordno=str_replace('BA','',$message['out_batch_no']);
            $cashed=Cash::where('ordno',$ordno)->find();
            if(!$cashed){
                return json(['code'=>503,'message'=>'提现订单不存在']);
            }
            return json(['code'=>200,'message'=>$message]);
        }catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'wxBatchNotify');
            return json(['code'=>503,'message'=>$e->getMessage()]);
        }
    }
    //虎皮椒支付回调
    public function xunNotify()
    {
        try {
            $payment = new Xunhu();
            $params = input();
            if (empty($params)) {
                exit('fail');
            }
            $order = Order::where('ordno', $params['trade_order_id'])->find();
            if (!$order || $order->status == 1) {
                exit('success');
            }
            $pay_ment = 'wechat';
            if ($order->pay_type == 2) {
                $pay_ment = 'alipay';
            }
            #处理虎皮椒微信小程序支付
            if(!empty($params['attach'])){
                $pay_ment = $params['attach'];
            }
            list($result, $message) = $payment->notify($params, $pay_ment);
            if ($result) {
                if ($message['status'] === 'OD') {//支付成功
                    $order->status = 1;
                    $order->ptime = time();
                    $order->utime = time();
                    $order->trade_id = $message['transaction_id'];
                    list($result, $info) = $this->handleOrder($order);
                    if (!$result) {
                        exit('fail');
                    }
                    exit('success');
                }
            }
            exit('fail');
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'xunNotify');
            exit('fail');
        }
    }

    //字节头条分账回调
    public function ttSettle()
    {
        try {
            Db::startTrans();
            $payment = new Toutiao();
            $params = file_get_contents("php://input");
            if (empty($params)) {
                return json(['err_no' => 400, 'err_tips' => 'business fail']);
            }
            list($result, $message) = $payment->notify($params);
            if ($result) {
                $order = Order::where('settle_ordno', $message['cp_settle_no'])->find();
                if (!$order || $order->settle_status == 2) {
                    return json(['err_no' => 0, 'err_tips' => 'success']);
                }
                if ($message['status'] === 'SUCCESS') {//支付成功
                    $order->settle_status = 2;
                    $order->utime = time();
                    $res = $order->save(); // 保存订单
                    if (!$res) {
                        doSyslog('保存订单失败@' . $order->ordno, 'ttSettle');
                        Db::rollback();
                        return json(['err_no' => 400, 'err_tips' => '====操作失败1']);
                    }
                    Db::commit();
                    return json(['err_no' => 0, 'err_tips' => 'success']);// 返回处理完成
                }
            }
            return json(['err_no' => 400, 'err_tips' => '通信失败，请稍后再通知我']);
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'ttNotify');
            return json(['err_no' => 400, 'err_tips' => $e->getMessage()]);
        }
    }

    //字节头条支付回调
    public function ttNotify()
    {
        try {
            $payment = new Toutiao();
            $params=input('post.');
            $timestamp=request()->header('byte-timestamp');
            $nonce=request()->header('byte-nonce-str');
            $signature=request()->header('byte-signature');
            if (empty($params)) {
                throw new \Exception('business fail');
            }
            list($result, $message) = $payment->newNotify($params,$timestamp,$nonce,$signature);
            if ($result) {
                $order = Order::where('ordno', $message['out_order_no'])->find();
                if (!$order || $order->status == 1) {
                    return json(['err_no' => 0, 'err_tips' => 'success']);
                }
                if ($message['status'] === 'SUCCESS') {//支付成功
                    $order->status = 1;
                    $order->ptime = time();
                    $order->utime = time();
                    $order->trade_id = $message['channel_pay_id'];
                    list($result, $info) = $this->handleOrder($order);
                    if (!$result) {
                        throw new \Exception($info);
                    }
                    return json(['err_no' => 0, 'err_tips' => 'success']);// 返回处理完成
                }
            }
            throw new \Exception('通信失败，请稍后再通知我');
        } catch (\Exception $e) {
            doSyslog($e->getMessage(), 'ttNotify');
            doSyslog($e->getTraceAsString(), 'ttNotify');
            return json(['err_no' => 400, 'err_tips' => $e->getMessage()]);
        }
    }

    //微信小程序支付回调
    public function wechatPay()
    {
        try {
            $payment = Facade::payment();
            $response = $payment->handlePaidNotify(function ($message, $fail) use ($payment) {
                doSyslog($message, 'wechatPay');//微信回调通知返回数据在$result
                $order = Order::where('ordno', $message['out_trade_no'])->find();
                if (!$order || $order->status == 1) {
                    return true;
                }
                $queryno = $payment->order->queryByOutTradeNumber($message['out_trade_no']);//查询订单
                if ($queryno['trade_state'] === 'SUCCESS') {//支付成功
                    $order->status = 1;
                    $order->ptime = strtotime($queryno['time_end']);
                    $order->utime = time();
                    $order->trade_id = $queryno['transaction_id'];
                    list($result, $info) = $this->handleOrder($order);
                    if (!$result) {
                        return $fail($info);
                    }
                    return true; // 返回处理完成
                }
                return $fail('通信失败，请稍后再通知我');
            });
            return $response;
        } catch (HttpException $e) {
            doSyslog($e->getMessage(), 'wechatPay');
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'wechatPay');
        }
    }

    /**
     * 微信公众号回调
     * @return [type] [description]
     */
    public function accountPay()
    {
        try {
            $payment = Facade::payment('official_account');
            $response = $payment->handlePaidNotify(function ($message, $fail) use ($payment) {
                doSyslog($message, 'accountPay');//微信回调通知返回数据在$result
                $order = Order::where('ordno', $message['out_trade_no'])->find();
                if (!$order || $order->status == 1) {
                    return true;
                }
                $queryno = $payment->order->queryByOutTradeNumber($message['out_trade_no']);//查询订单
                if ($queryno['trade_state'] === 'SUCCESS') {//支付成功
                    $order->status = 1;
                    $order->ptime = strtotime($queryno['time_end']);
                    $order->utime = time();
                    $order->trade_id = $queryno['transaction_id'];
                    list($result, $info) = $this->handleOrder($order);
                    if (!$result) {
                        return $fail($info);
                    }
                    return true; // 返回处理完成
                }
                return $fail('通信失败，请稍后再通知我');
            });
            return $response;
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage(), 'accountPay');
        }
    }

    /**
     * 支付宝回调
     * @return [type] [description]
     */
    public function alipay()
    {
        try {
            $aliPay = new \alipay\notify(config('setting.ali_pay_public_key'));
            $data = input('post.');
            $result = $aliPay->rsaCheck($data, $data['sign_type']);
            if ($result === true) {
                $order = Order::where('ordno', $data['out_trade_no'])->find();
                if (!$order || $order->status == 1) {
                    return 'success';
                }
                $order->status = 1;
                $order->ptime = strtotime($data['gmt_payment']);
                $order->utime = time();
                $order->trade_id = $data['trade_no'];
                list($result, $info) = $this->handleOrder($order);
                if (!$result) {
                    return 'error';
                }
                return 'success';
            } else {
                return 'error';
            }
        } catch (\think\Exception $e) {
            return 'error';
        }
    }

    /**
     * 处理订单
     * @param object $order
     * @return array
     * @throws \think\exception\DbException
     */
    protected function handleOrder($order)
    {
        try {
            Db::startTrans();
            //用户分销佣金
            if ($order->type !== 3 || config('setting.is_cdkey_income') == '1') {
                if ($order->hr_money > 0 && $order->pid > 0) {
                    list($res, $info) = Bill::money(1, 1, $order->hr_money, $order->pid, '一级推广佣金', $order->id);
                    if (!$res) {
                        doSyslog($info . '用户分销@' . $order->ordno, 'handleOrder');
                        Db::rollback();
                        return [false, '操作失败1'];
                    }
                }
                if ($order->sr_money > 0 && $order->pid > 0) {
                    $puser = User::where('id', $order->pid)->find();
                    if ($order->sr_money > 0 && $puser->pid > 0) {
                        list($res, $info) = Bill::money(1, $order->type, $order->sr_money, $puser->pid, '二级推广佣金', $order->id);
                        if (!$res) {
                            doSyslog($info . '用户分销@' . $order->ordno, 'handleOrder');
                            return [false, $info];
                        }
                    }
                }
            }
            //购买资源
            if ($order->type === 1) {
                #更新销量
                $spread = Spread::where('id', $order->rid)->find();
                if ($spread) {
                    $spread->sales = ['inc', 1];
                    $spread->save();
                }
                #todo 卡密多次购买
                $work_id = 0;
                if ($spread->type == 6) {
                    $kam = ResourceKammi::where(['rid' => $spread->rid, 'uid' => 0])->find();
                    if (empty($kam)) {
                        Db::rollback();
                        doSyslog('卡密库存不足，购买失败', 'handleOrder');
                        return [false, '操作失败1'];
                    }
                    $kam->uid = $order->uid;
                    $kam->utime = time();
                    $kam->save();
                    $work_id = $kam->id;
                }
                $arr = [
                    'rid' => $order->rid,
                    'uid' => $order->uid,
                    'work_id' => $work_id,
                    'type' => 1,
                    'admin_id' => $order->admin_id
                ];
                $res = UserResource::create($arr);
                if (!$res) {
                    doSyslog($res . '==操作失败2', 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败1'];
                }
            } elseif ($order->type === 2) {
                $user = User::where('id', $order->uid)->find();
                if (!$user) {
                    doSyslog($res . '==操作失败3', 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败3'];
                }
                $vip = Svip::where('id', $order->vid)->find();
                if (!$vip) {
                    doSyslog($res . '==操作失败4', 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败4'];
                }
                $exp_time = $user->exp_time;
                if ($exp_time < time()) {
                    $exp_time = time();
                }
                $user->vid = $order->vid;
                $user->exp_time = $exp_time + ($vip->days * 86400);
                if (!$user->save()) {
                    doSyslog($res . '==操作失败5', 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败5'];
                }
                #处理分站订单
                if ($vip->type == 1) {
                    $siteOrder = SiteOrder::where('ordno', $order->ordno)->find();
                    if (!$siteOrder) {
                        doSyslog('分站订单不存在==操作失败5', 'handleOrder');
                        Db::rollback();
                        return [false, '操作失败5'];
                    }
                    $data = [
                        'ordno' => $order->ordno,
                        'money' => $order->money
                    ];
                    list($res, $msg) = SiteOrder::createSiteOrder($data, 1);
                    if (!$res) {
                        doSyslog($msg . '==操作失败5', 'handleOrder');
                        Db::rollback();
                        return [false, $msg];
                    }
                }
            } elseif ($order->type === 3) {//购买查看社群
                #更新销量
                $groups = Groups::where('id', $order->rid)->find();
                if ($groups) {
                    $groups->views = ['inc', 1];
                    $groups->save();
                }
                $arr = [
                    'group_id' => $order->rid,
                    'uid' => $order->uid,
                    'type' => 1,
                    'admin_id' => $order->admin_id
                ];
                $res = GroupsLog::create($arr);
                if (!$res) {
                    doSyslog($res . '==操作失败2', 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败1'];
                }
            }
            #============处理代理收益=================
            $is_kl = 0;
            $agent = Admin::where('id', $order->admin_id)->find();//扣量判断
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
            #todo 处理分成和分佣 超过100%
            if ($min_take > 0 && $is_kl == 0 && $agent->admin_id > 0) {
                $take_money = bcdiv(bcmul($order->money, $min_take, 2), 100, 2);
                if ($take_money && $money > $take_money) {
                    $money = bcsub($money, $take_money, 2);
                } else {
                    doSyslog($order->money . '==' . $order->hr_money . '==' . $order->sr_money . '==' . $take_money . '佣金比例设置超过100%', 'handleOrder');
                    $take_money = 0;
                }
            }

            if ($order->type !== 3 || config('setting.is_cdkey_income') == '1') {
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
                    doSyslog($money . '@' . $remark . '@' . $order->ordno, 'handleOrder');
                    Db::rollback();
                    return [false, '操作失败1'];
                }
            }
            #提成
            if ($order->type !== 3 || config('setting.is_cdkey_income') == '1') {
                if ($take_money && $is_kl == 0 && $agent->admin_id > 0) {
                    $remark = "【抽成】单号:{$order->ordno};提成抽取比例{$agent->min_take}%;代理【{$agent->username}】ID:{$agent->id}";
                    list($res, $info) = AdminBill::money(1, 3, $take_money, $agent->admin_id, $remark, $order->id);
                    if (!$res) {
                        doSyslog($info . '分销抽成@' . $order->ordno, 'handleOrder');
                        Db::rollback();
                        return [false, '操作失败2'];
                    }
                }
            }
            $order->pt_money = $money;
            $order->tc_money = $take_money;
            $order->is_kl = $is_kl;
            $res = $order->save(); // 保存订单
            if (!$res) {
                doSyslog('保存订单失败@' . $order->ordno, 'handleOrder');
                Db::rollback();
                return [false, '操作失败3'];
            }
            Db::commit();
            return [true, 'success'];
        } catch (\think\exception\DbException $e) {
            doSyslog($e->getMessage() . '@' . $order->ordno, 'handleOrder');
            return [false, $e->getMessage()];
        } catch (\think\Exception $e) {
            doSyslog($e->getMessage() . '@' . $order->ordno, 'handleOrder');
            return [false, $e->getMessage()];
        }
    }

    /**
     * 微信退款回调
     */
    public function wechatRefund()
    {
        try {
            $app = Facade::payment('official_account');
            $response = $app->handleRefundedNotify(function ($message, $reqInfo, $fail) {
                if ($message['return_code'] == 'SUCCESS') {
                    $order = Order::where(['ordno' => $reqInfo['out_trade_no'], 'refund_status' => 1])->find();
                    if (!$order) {
                        $fail('未找到订单');
                    }
                    $refund = RefundLog::where(['ordno' => $reqInfo['out_trade_no'], 'refund_ordno' => $reqInfo['out_refund_no'], 'status' => 1])->find();
                    if (!$refund) {
                        $fail('未找到订单');
                    }
                    if ($reqInfo['refund_status'] == 'SUCCESS') {
                        #更新退款单
                        $refund->trade_id = $reqInfo['refund_id'];
                        $refund->status = 2;
                        $refund->utime = time();
                        $refund->save();
                        #更新订单
                        $order->refund_status = 2;
                        $order->save();
                    } else {
                        #更新退款单
                        $refund->trade_id = $reqInfo['refund_id'];
                        $refund->reason = $reqInfo['refund_status'];
                        $refund->status = -2;
                        $refund->utime = time();
                        $refund->save();
                        #更新订单
                        $order->refund_status = -1;
                        $order->save();
                    }
                    return true;
                } else {
                    $fail($message['return_msg']);
                }
            });
            $response->send();
        } catch (\EasyWeChat\Kernel\Exceptions\Exception $e) {
            doSyslog($e->getMessage(), 'wechatRefund');
        }
    }

    /**
     * 退款处理用户收益
     */
    protected function refundBill($order)
    {
        #用户分佣一级
        if ($order->hr_money > 0) {
            list($res, $info) = Bill::money(2, $order->type, $order->hr_money, $order->pid, '用户退款', $order->id);
            if (!$res) {
                return [false, '一级分佣退款用户余额不足，' . $info];
            }
        }
        #用户分佣二级
        if ($order->sr_money > 0) {
            $top_pid = \app\common\model\User::where('id', $order->pid)->value('pid');
            if ($top_pid > 0) {
                list($res, $info) = Bill::money(2, $order->type, $order->sr_money, $top_pid, '用户退款', $order->id);
                if (!$res) {
                    return [false, '二级分佣退款用户余额不足，' . $info];
                }
            }
        }
        #代理抽成收益
        if ($order->tc_money > 0) {
            $agent = Admin::where('id', $order->admin_id)->find();
            if ($agent) {
                $remark = "【抽成】单号:{$order->ordno};退款:{$order->tc_money}";
                list($res, $info) = AdminBill::money(2, 3, $order->tc_money, $agent->admin_id, $remark, $order->id);
                if (!$res) {
                    return [false, '抽成退款代理余额不足，' . $info];
                }
            }
        }
        #代理收益
        if ($order->pt_money > 0) {
            $remark = "【收益】单号:{$order->ordno};退款:{$order->tc_money}";
            list($res, $info) = AdminBill::money(2, 3, $order->pt_money, $order->admin_id, $remark, $order->id);
            if (!$res) {
                return [false, '收益退款代理余额不足，' . $info];
            }
        }
        return [true, '退款金额回退成功'];
    }
}