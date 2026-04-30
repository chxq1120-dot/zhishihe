<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

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
            $data = input('param.');
            $this->searchvar($data);
            $list = orderModel::withSearch(
                ['ctime', 'name', 'status'],
                ['ctime' => [$this->start, $this->end],
                    'name' => $this->search,
                    'status' => $this->status
                ])
                ->withJoin(['user' => ['nickname', 'mobile'], 'agent' => ['username', 'id'], 'spread' => ['title', 'thumb'], 'svip' => ['name']])
                ->where(['order.admin_id' => $this->admin_uid, 'is_kl' => 0])
                ->order('order.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->view->fetch();
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
                ->where('a.admin_id', $this->admin_uid)
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
            $list->where('a.admin_id', $this->admin_uid);
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
                ->where('resource_task.admin_id', $this->admin_uid)
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
            $list = UserResource::withJoin(['user' => ['nickname'], 'spread' => ['title']])
                ->where('user_resource.admin_id', $this->admin_uid)
                ->order('user_resource.id desc');
            $list = $list->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->view->fetch();
    }
}