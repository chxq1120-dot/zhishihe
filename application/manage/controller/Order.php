<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\AdminBill;
use app\common\model\Order as orderModel;
use app\common\model\Bill;
use app\common\model\Invite;
use app\common\model\RefundLog;
use app\common\model\Spread;
use app\common\model\ResourceTask;
use app\common\model\SpreadHand;
use app\common\model\UserResource;
use Naixiaoxin\ThinkWechat\Facade;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Order extends Common
{
    public function initialize(){
        parent::initialize();
        $this->model=new orderModel();
    }
    /**
     * 全部订单
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);
            $list = orderModel::withSearch(
                ['ctime', 'name', 'status'],
                ['ctime' => [$this->start, $this->end],
                    'name' => $this->search,
                    'status' => $this->status
                ])
                ->withJoin(['user' => ['nickname', 'mobile'], 'agent' => ['username', 'id'], 'spread' => ['title', 'thumb'], 'svip' => ['name']])
                ->order('order.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);

        }
        return $this->view->fetch();
    }
    /**
     * 订单退款
     */
    public function refund()
    {
        try {
            if ($this->request->isPost()) {
                $ids = $this->request->param('ids/d', 0);
                $order = orderModel::where(['id' => $ids, 'status' => 1])->where('refund_status', 'in', [0, -1])->find();
                if (!$order) {
                    return callback(400, '订单不支持退款');
                }
                Db::startTrans();
                $refund = RefundLog::where(['ordno' => $order->ordno])->find();
                if (!$refund) {
                    $refund_no = date('YmdHis') . mt_rand(1111111, 9999999);
                    $data = [
                        'ordno' => $order->ordno,
                        'type' => $order->pay_type,
                        'refund_ordno' => $refund_no,
                        'money' => $order->money,
                        'refund_money' => $order->money,
                        'remark' => '用户申请退款',
                        'ctime' => time()
                    ];
                    $refund = RefundLog::create($data, true);
                }
                #收益退款
                list($res, $msg) = $this->refundBill($order);
                if (!$res) {
                    $refund->status = -1;
                    $refund->reason = $msg;
                    $refund->utime = time();
                    $refund->save();
                    #更新订单状态
                    $order->refund_status = -1;
                    $order->remark = $msg;
                    $order->save();
                    Db::commit();
                    return callback(400, $msg);
                }
                #更新退款单状态
                $refund->status = 2;
                $refund->reason = '退款操作成功';
                $refund->utime = time();
                $refund->save();
                #更新订单状态
                $order->refund_status = 2;
                $order->save();
                Db::commit();
                return callback(200, '退款操作成功');
            }
        } catch (Exception $e) {
            return callback(400, $e->getMessage());
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
            $remark = "【收益】单号:{$order->ordno};退款:{$order->pt_money}";
            list($res, $info) = AdminBill::money(2, 3, $order->pt_money, $order->admin_id, $remark, $order->id);
            if (!$res) {
                return [false, '收益退款代理余额不足，' . $info];
            }
        }
        return [true, '退款金额回退成功'];
    }

    /**
     * 删除
     */
    public function delorder()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $arr = [];
            foreach ($data['data'] as $key => $value) {
                $arr[] = $value['id'];
            }
            $i = (new orderModel)->destroy($arr);
            if ($i) {
                return callback(200, '成功');
            } else {
                return callback(400, '失败');
            }
        }
    }

    /**
     * 删除未支付订单
     * @return [type] [description]
     */
    public function delnopay()
    {
        $res = orderModel::where('status', 0)->delete();
        if (!$res) {
            return callback(404, '删除失败');
        }
        return callback(200, '删除成功');
    }

    /**
     * 分润记录
     * @return [type] [description]
     */
    public function rlist()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);
            $list = Bill::alias('a')->where('a.type', 'in', '1,2')
                ->field('a.*,b.nickname,b.avatar,d.nickname as onickname,d.avatar as oavatar,c.ordno,c.money as omoney')
                ->join('user b', 'a.uid=b.id')
                ->join('order c', 'a.order_id=c.id')
                ->join('user d', 'c.uid=d.id')
                ->order('a.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->fetch();
    }

    /**
     * 助力记录
     * @return [type] [description]
     */
    public function zlist()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);

            $hand_type=intval(config('setting.spread_hand_type'));
            if($hand_type==0){
                $list = Invite::alias('a')
                    ->field('a.*,b.nickname,d.nickname as pnickname,c.title,a.ctime')
                    ->join('user b', 'a.uid=b.id')
                    ->join('spread c', 'a.rid=c.id')
                    ->join('user d', 'a.pid=d.id')
                    ->order('a.id desc');
            }else{
                $list = SpreadHand::alias('a')
                    ->join('user b', 'a.uid=b.id')
                    ->join('spread c', 'a.rid=c.id')
                    ->join('user d', 'a.r_uid=d.id')
                    ->field('a.*,b.nickname,d.nickname as pnickname,c.title,a.ctime')
                    ->order('a.id desc');
            }
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->fetch();
    }

    /**
     * 任务记录
     * @return [type] [description]
     */
    public function tlist()
    {
        if ($this->request->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);
            $list = ResourceTask::withSearch(
                ['ctime', 'name', 'status'],
                ['ctime' => [$this->start, $this->end],
                    'name' => $this->search,
                    'status' => $this->status
                ])
                ->withJoin(['user' => ['nickname'], 'spread' => ['title', 'invite', 'invite_num', 'exc_video']])
                ->order('resource_task.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);

        }
        return $this->view->fetch();
    }

    /**
     * 会员获取记录
     * @return [type] [description]
     */
    public function ulist()
    {
        if ($this->request->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);
            $list = UserResource::withJoin(['user' => ['nickname'], 'spread' => ['title']])->order('user_resource.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->view->fetch();
    }
}