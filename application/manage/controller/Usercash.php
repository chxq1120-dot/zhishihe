<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\Bill;
use app\common\model\UserCash as UserCashModel;
use app\common\model\User;
use app\common\model\UserSubscribe;
use app\common\model\UserThird;
use app\common\model\WxPay;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use Naixiaoxin\ThinkWechat\Facade;

class Usercash extends Common
{
    protected $searchFields = 'user_cash.ordno,user_cash.realname,user_cash.account,user_cash.bankname';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $relationSearch = true;
    protected $dataLimit = 'personal';
    public function initialize()
    {
        parent::initialize();
        $this->model = new UserCashModel();
    }

    /**
     * 提现记录
     */
    public function list()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if($this->request->request('keyField')){
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['user' => ['nickname', 'avatar'],'agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['user' => ['nickname', 'avatar'],'agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 提现审核
     * @return [type] [description]
     */
    public function passed()
    {
        if ($this->request->isPost()) {
             try{
                $data = $this->request->param('row/a');
                Db::startTrans();
                $info = UserCashModel::where('id', $data['ids'])->find();
                if ($info->status == -1 || $info->status == 2) {
                    return callback(400, '审核操作失败');
                }
                $uid = $info->uid;
                $userinfo = User::where(['id' => $uid, 'status' => 1])->find();
                if (empty($userinfo)) {
                    return callback(400, '审核操作失败，请检查用户状态是否正常');
                }
                if ($data['status'] == -1) {//未通过
                    $info->status = -1;
                    $info->reason = $data['reason'];
                    $info->utime = time();
                    list($res, $msg) = Bill::money(1, 3, $info->money, $info->uid, '提现失败回退', $info->id);
                    if (!$res) {
                        Db::rollback();
                        return callback(400, $msg);
                    }
                    if (!$info->save()) {
                        Db::rollback();
                        return callback(400, '操作失败2');
                    }
                } elseif ($data['status'] == 1) {#审核通过
                    $info->status = 1;
                    $info->reason = $data['reason'];
                    $info->utime = time();
                    if (!$info->save()) {
                        Db::rollback();
                        return callback(400, '操作失败3');
                    }
                    $openid = (new UserThird())->getFieldVal(1, $userinfo->id, 'openid');
                    switch ($info->type) {
                        case 2:#微信零钱提现自动执行
                            if(config("setting.cash_iswechat")=='2' && empty($info->image)){
                                if (empty($openid)) {
                                    return callback(400, '提现自动转账操作失败');
                                }
                                switch (intval(config("setting.cash_weixin_type"))){
                                    case 0:#企业付款到零钱
                                        $app = Facade::payment();
                                        $result = $app->transfer->toBalance([
                                            'partner_trade_no' => $info->ordno,
                                            'openid' => $openid,
                                            'check_name' => 'FORCE_CHECK',
                                            're_user_name' => $userinfo->realname,
                                            'amount' => $info->amount * 100,
                                            'desc' => '提现',
                                        ]);
                                        if ($result['return_code'] === 'FAIL' || $result['result_code'] === 'FAIL') {
                                            Db::rollback();
                                            return callback(400, $result['err_code_des']);
                                        }
                                        break;
                                    case 1:#商家付款到零钱
                                        $wxPay = new WxPay();
                                        $body = [
                                            'appid' => config('setting.app_id'),
                                            'out_bill_no' => $info->ordno,
                                            'transfer_scene_id'=>config('setting.cash_scene_id'),
                                            'openid' => $openid,
                                            'transfer_amount' => $info->amount * 100,
                                            'transfer_remark'=>$info->realname . '的' . $info->ordno . '订单佣金提现',
                                            'transfer_scene_report_infos' => [[
                                                'info_type' => '岗位类型',
                                                'info_content' => '分销员',
                                            ],[
                                                'info_type' => '报酬说明',
                                                'info_content' => '分销佣金发放',
                                            ]]
                                        ];
                                        list($result, $message) = $wxPay->payBatcheBills($body);
                                        if (!$result) {
                                            Db::rollback();
                                            return callback(400, $message);
                                        }
                                        if($message['state']=='WAIT_USER_CONFIRM'){
                                            $info->package=$message['package_info'];
                                            $info->confirm=1;
                                        }
                                        break;
                                }
                                if (!empty($openid)){
                                    //订阅消息
                                    $template_id = config('setting.subscribe_cashed_id');
                                    $i = UserSubscribe::where('temp_id', $template_id)->where(['uid' => $userinfo->id, 'result' => 'accept'])->find();
                                    if ($i) {
                                        $app = Facade::miniProgram();
                                        $arr = [
                                            'template_id' => $template_id,
                                            'touser' => $openid,
                                            'page' => '/pages/my/wallet',
                                            'data' => [
                                                'character_string5' => ['value' => $info->ctime . $userinfo->id],
                                                'amount2' => ['value' => $info->money],
                                                'thing8' => ['value' => '手续费' . $info->fee . ',实际到账' . $info->amount],
                                                'time9' => ['value' => date('Y.m.d H:i')]
                                            ]
                                        ];
                                        $app->subscribe_message->send($arr);
                                    }
                                }
                                $info->status = 2;
                                $info->reason = '提现打款完成';
                                $info->utime = time();
                                if (!$info->save()) {
                                    return callback(400, '操作失败3');
                                }
                            }
                            break;
                        case 1:#支付宝转账
                            if(config("setting.cash_isalipay")=='2' && empty($info->image)) {
                                $alipay = new \alipay\wap();
                                $alipay->setAppid(config('setting.ali_pay_appid'));
                                $alipay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                $alipay->setAlipayCertPublic(config('setting.ali_pay_public_key'));
                                $alipay->setAlipayRootContent(config('setting.ali_pay_root_key'));
                                $alipay->setAppCertContent(config('setting.ali_pay_app_public'));
                                $alipay->setOutBizNo($info->ordno);
                                $alipay->setTransAmount($info->amount);
                                $alipay->setOrderTitle('用户提现');
                                $alipay->setRemark('用户佣金提现');
                                $alipay->setPayeeInfo($info->account, $info->realname);
                                $result = $alipay->transPay();
                                if (empty($result['code']) || $result['code'] != 10000) {
                                    return callback(400, $result['sub_msg']);
                                }
                                if (!empty($openid)){
                                    //订阅消息
                                    $template_id = config('setting.subscribe_cashed_id');
                                    $i = UserSubscribe::where('temp_id', $template_id)->where(['uid' => $userinfo->id, 'result' => 'accept'])->find();
                                    if ($i) {
                                        $app = Facade::miniProgram();
                                        $arr = [
                                            'template_id' => $template_id,
                                            'touser' => $openid,
                                            'page' => '/pages/my/wallet',
                                            'data' => [
                                                'character_string5' => ['value' => $info->ctime . $userinfo->id],
                                                'amount2' => ['value' => $info->money],
                                                'thing8' => ['value' => '手续费' . $info->fee . ',实际到账' . $info->amount],
                                                'time9' => ['value' => date('Y.m.d H:i')]
                                            ]
                                        ];
                                        $app->subscribe_message->send($arr);
                                    }
                                }
                                $info->status = 2;
                                $info->reason = '提现打款完成';
                                $info->utime = time();
                                if (!$info->save()) {
                                    return callback(400, '操作失败3');
                                }
                            }
                            break;
                    }
                }
                Db::commit();
                return callback(200, '操作成功');
            }catch (Exception $e){
                return callback(400, $e->getMessage());
            }
        }
        return $this->fetch();
    }

    /**
     * 已打款
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function finished()
    {
        if ($this->request->isPost()) {
            try {
                $ids = $this->request->param('ids/d');
                $info = UserCashModel::where('id', $ids)->find();
                if ($info->status == -1 || $info->status == 2) {
                    return callback(400, '审核操作失败');
                }
                $uid = $info->uid;
                $userinfo = User::where('id', $uid)->find();
                $openid = (new UserThird())->getFieldVal(1, $userinfo->id, 'openid');
                if ($info->status == 1) {
                    switch ($info->type) {
                        case 2:#微信
                            if (config("setting.cash_iswechat") == '2' && empty($info->image)) {
                                if (empty($openid)) {
                                    return callback(400, '提现自动转账操作失败');
                                }
                                switch (intval(config("setting.cash_weixin_type"))) {
                                    case 0:#企业付款到零钱
                                        $app = Facade::payment();
                                        $result = $app->transfer->toBalance([
                                            'partner_trade_no' => $info->ordno,
                                            'openid' => $openid,
                                            'check_name' => 'FORCE_CHECK',
                                            're_user_name' => $userinfo->realname,
                                            'amount' => $info->amount * 100,
                                            'desc' => '提现',
                                        ]);
                                        if ($result['return_code'] === 'FAIL' || $result['result_code'] === 'FAIL') {
                                            Db::rollback();
                                            return callback(400, $result['err_code_des']);
                                        }
                                        break;
                                    case 1:#商家付款到零钱
                                        $wxPay = new WxPay();
                                        $body = [
                                            'appid' => config('setting.app_id'),
                                            'out_bill_no' => $info->ordno,
                                            'transfer_scene_id'=>config('setting.cash_scene_id'),
                                            'openid' => $openid,
                                            'transfer_amount' => $info->amount * 100,
                                            'transfer_remark'=>$info->realname . '的' . $info->ordno . '订单佣金提现',
                                            'transfer_scene_report_infos' => [[
                                                'info_type' => '岗位类型',
                                                'info_content' => '分销员',
                                            ],[
                                                'info_type' => '报酬说明',
                                                'info_content' => '分销佣金发放',
                                            ]]
                                        ];
                                        list($result, $message) = $wxPay->payBatcheBills($body);
                                        if (!$result) {
                                            Db::rollback();
                                            return callback(400, $message);
                                        }
                                        if($message['state']=='WAIT_USER_CONFIRM'){
                                            $info->package=$message['package_info'];
                                            $info->confirm=1;
                                        }
                                        break;
                                }
                            }
                            break;
                        case 1:#支付宝
                            if (config("setting.cash_isalipay") == '2' && empty($info->image)) {
                                $alipay = new \alipay\wap();
                                $alipay->setAppid(config('setting.ali_pay_appid'));
                                $alipay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                $alipay->setAlipayCertPublic(config('setting.ali_pay_public_key'));
                                $alipay->setAlipayRootContent(config('setting.ali_pay_root_key'));
                                $alipay->setAppCertContent(config('setting.ali_pay_app_public'));
                                $alipay->setOutBizNo($info->ordno);
                                $alipay->setTransAmount($info->amount);
                                $alipay->setOrderTitle('用户提现');
                                $alipay->setRemark('用户佣金提现');
                                $alipay->setPayeeInfo($info->account, $info->realname);
                                $result = $alipay->transPay();
                                if (empty($result['code']) || $result['code'] != 10000) {
                                    return callback(400, $result['sub_msg']);
                                }
                            }
                            break;
                    }
                }
                if (!empty($openid)) {
                    //订阅消息
                    $template_id = config('setting.subscribe_cashed_id');
                    $i = UserSubscribe::where('temp_id', $template_id)->where(['uid' => $userinfo->id, 'result' => 'accept'])->find();
                    if ($i) {
                        $app = Facade::miniProgram();
                        $arr = [
                            'template_id' => $template_id,
                            'touser' => $openid,
                            'page' => '/pages/my/wallet',
                            'data' => [
                                'character_string5' => ['value' => $info->ctime . $userinfo->id],
                                'amount2' => ['value' => $info->money],
                                'thing8' => ['value' => '手续费' . $info->fee . ',实际到账' . $info->amount],
                                'time9' => ['value' => date('Y.m.d H:i')]
                            ]
                        ];
                        $app->subscribe_message->send($arr);
                    }
                }
                $info->status = 2;
                $info->reason = '提现打款完成';
                $info->utime = time();
                if (!$info->save()) {
                    return callback(400, '操作失败3');
                }
                return callback(200, '操作成功');
            }catch (Exception $e){
                return callback(400, $e->getMessage());
            }
        }
    }
}