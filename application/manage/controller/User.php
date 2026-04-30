<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\ResourceLevel;
use app\common\model\ResourceTask;
use app\common\model\SvipLog;
use app\common\model\{
    User as UsersModel,
    Svip,
    Privilege,
    ResourceSort,
    SvipPrivilege,
    Order,
    SvipTask,
    UserResource,
    Bill,
};
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class User extends Common
{
    protected $searchFields = 'user.username,user.mobile,user.realname,user.nickname,invite.id,invite.nickname';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'user';
    protected $dataLimit = 'personal';

    public function initialize()
    {
        parent::initialize();
        $this->model = new UsersModel();
    }

    /**
     * 列表
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['agent' => ['username', 'id'], 'invite' => ['nickname', 'id']], 'left')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['agent' => ['username', 'id'], 'invite' => ['nickname', 'id']], 'left')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                if (empty($v['exp_time'])) {
                    $list[$k]['exp_time_text'] = '无';
                } else {
                    $list[$k]['exp_time_text'] = date('Y-m-d H:i:s', $v['exp_time']);
                }
                if (time() < $v['exp_time']) {
                    $list[$k]['is_vip'] = 1;
                } else {
                    $list[$k]['is_vip'] = 0;
                }
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        $sviplist = Svip::where('admin_id', $this->admin_uid)->order('level asc')->select();
        $this->assign('sviplist', $sviplist);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    #删除第三方登录
                    \app\common\model\UserThird::where('uid', $v['id'])->delete();
                    #删除提现
                    \app\common\model\UserCash::where('uid', $v['id'])->delete();
                    #删除收藏
                    \app\common\model\UserColl::where('uid', $v['id'])->delete();
                    #删除资源
                    \app\common\model\UserResource::where('uid', $v['id'])->delete();
                    #删除任务
                    \app\common\model\ResourceTask::where('uid', $v['id'])->delete();
                    #删除会员余额明细
                    \app\common\model\Bill::where('uid', $v['id'])->delete();
                    #删除订阅记录
                    \app\common\model\UserSubscribe::where('uid', $v['id'])->delete();
                    #删除升级记录
                    \app\common\model\SvipLog::where('uid', $v['id'])->delete();
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($count) {
                return callback(200, '删除成功');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 我的推广
     * @return [type] [description]
     */
    public function spread()
    {
        $uid = input('param.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            $list = UsersModel::where('pid', $uid)
                ->order('id desc')
                ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                ->toArray();
            return json(['code' => 0, 'msg' => '成功', 'count' => $list['total'], 'data' => $list['data']]);
        }
        return $this->fetch();
    }

    /**
     * svip设置列表
     * @return [type] [description]
     */
    public function sviplist()
    {
        if (request()->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->searchFields = 'svip.name,svip.content';
            $this->model=new Svip();
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $pri = SvipPrivilege::alias('a')->field('b.id,b.name')
                    ->where('a.vid', $v['id'])
                    ->join('privilege b', 'a.pid=b.id')->select();
                $list[$k]['privilege'] = $pri;
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->fetch();
    }

    /**
     * 更新单个字段值
     */
    public function setVipUp($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        $this->model = new Svip();
        if ($ids) {
            if ($this->request->has('params') && !empty($this->request->post("params"))) {
                $values = json_decode($this->request->post("params"), true);
                $count = 0;
                Db::startTrans();
                try {
                    $pk = $this->model->getPk();
                    $list = $this->model->where($pk, 'in', $ids)->select();
                    foreach ($list as $item) {
                        $count += $item->allowField(true)->isUpdate(true)->save($values);
                    }
                    Db::commit();
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($count) {
                    return callback(200, '更新成功');
                } else {
                    return callback(404, '更新失败');
                }
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 添加vip
     * @return [type] [description]
     */
    public function addsvip()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->admin_uid;
            }
            $data['status'] = 1;
            $info = Svip::create($data);
            if ($info) {
                foreach ($data['pid'] as $k => $v) {
                    $arr[] = ['pid' => $v, 'vid' => $info->id];
                }
                (new SvipPrivilege)->saveAll($arr);
                return callback(200, '添加成功');
            } else {
                return callback(400, '添加失败');
            }
        }
        $this->assign('min_price', 0);
        return $this->fetch();

    }

    public function getprivilege()
    {
        $list = Privilege::all();
        foreach ($list as $k => $v) {
            if ($v['type'] == 5) {
                $rule = unserialize($v['rule']);
                if (!is_array($rule)) {
                    $list[$k]['rule'] = ['sell' => bcmul($rule, 100, 0) . '%', 'sell2' => '0%'];
                } else {
                    $list[$k]['rule'] = ['sell' => bcmul($rule['sell'], 100, 0) . '%', 'sell2' => bcmul($rule['sell2'], 100, 0) . '%'];
                }
            } else {
                $list[$k]['rule'] = unserialize($v['rule']);
            }
        }
        $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list, 'count' => $list];
        return json($result);
    }

    /**
     * 编辑vip
     * @return [type] [description]
     */
    public function editsvip()
    {
        $id = input('get.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            Svip::where('id', $id)->update($data);
            SvipPrivilege::where('vid', $id)->delete();
            foreach ($data['pid'] as $k => $v) {
                $arr[] = ['pid' => $v, 'vid' => $id];
            }
            (new SvipPrivilege)->saveAll($arr);
            return callback(200, '编辑成功');
        }
        $this->assign('min_price', 0);
        $info = Svip::where('id', $id)->find();
        $this->assign('info', $info);
        $privilege = SvipPrivilege::alias('a')->field('a.id,a.pid,b.name')->where('vid', $id)->join('privilege b', 'a.pid=b.id')->select();
        $this->assign('privilege', $privilege);
        return $this->fetch();
    }

    /**
     * 删除vip
     * @return [type] [description]
     */
    public function delsvip($ids="")
    {
        if ($ids) {
            $this->model=new Svip();
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    \app\common\model\SvipPrivilege::where('vid', $v['id'])->delete();
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($count) {
                return callback(200, '删除成功');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 特权
     * @return [type] [description]
     */
    public function privilege()
    {
        if (request()->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->searchFields = 'name,desc';
            $this->model=new Privilege();
            $this->relationSearch = false;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model->where($where)->order($sort, $order)->paginate(['list_rows' => $limit, 'page' => input('get.page')])->toArray();
            foreach ($list['data'] as $k => $v) {
                if ($v['type'] == 5) {
                    $rule = unserialize($v['rule']);
                    if (!is_array($rule)) {
                        $list['data'][$k]['rule'] = ['sell' => bcmul($rule, 100, 0) . '%', 'sell2' => '0%'];
                    } else {
                        $list['data'][$k]['rule'] = ['sell' => bcmul($rule['sell'], 100, 0) . '%', 'sell2' => bcmul($rule['sell2'], 100, 0) . '%'];
                    }
                } else {
                    $list['data'][$k]['rule'] = unserialize($v['rule']);
                }
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list['data'], 'total' => $list['total']];
            return json($result);
        }
        return $this->fetch();
    }
    /**
     * 更新单个字段值
     */
    public function setPriUp($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        $this->model = new Privilege();
        if ($ids) {
            if ($this->request->has('params') && !empty($this->request->post("params"))) {
                $values = json_decode($this->request->post("params"), true);
                $count = 0;
                Db::startTrans();
                try {
                    $pk = $this->model->getPk();
                    $list = $this->model->where($pk, 'in', $ids)->select();
                    foreach ($list as $item) {
                        $count += $item->allowField(true)->isUpdate(true)->save($values);
                    }
                    Db::commit();
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($count) {
                    return callback(200, '更新成功');
                } else {
                    return callback(404, '更新失败');
                }
            }
        }
        return callback(404, '参数ids不能为空');
    }
    /**
     * 保存特权
     * @return [type] [description]
     */
    public function saveprivilege()
    {
        $id = input('get.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            $arr['name'] = $data['name'];
            $arr['url'] = $data['url'];
            $arr['desc'] = $data['desc'];
            if ($data['type'] == 1) {
                if (!$data['level']) {
                    return callback(400, '至少勾选一个');
                }
                $arr1 = [];
                foreach ($data['level'] as $k => $v) {
                    $value['level'] = $k;
                    $value['name'] = $v;
                    $arr1[] = $value;
                }
            } elseif ($data['type'] == 2) {
                if (!$data['num']) {
                    return callback(400, '请正确填写');
                }
                $arr1 = $data['num'];
            } elseif ($data['type'] == 3) {
                if (!$data['sort_id']) {
                    return callback(400, '至少勾选一个');
                }
                $arr1 = [['id' => 0, 'name' => '全部分类']];
                if (!in_array('0', $data['sort_id'])) {
                    $arr1 = ResourceSort::field('id,name')->where('id', 'in', $data['sort_id'])->select()->toArray();
                }
            } elseif ($data['type'] == 4) {
                if ($data['adv'] == 0) {
                    $arr1 = [
                        'status' => 0,
                        'name' => '无广告'
                    ];
                } else {
                    $arr1 = [
                        'status' => 1,
                        'name' => '有广告'
                    ];
                }
            } elseif ($data['type'] == 5) {
                if (empty($data['sell'])) {
                    return callback(400, '请填写一级佣金');
                }
                $sell = $data['sell'];
                $sell2 = 0;
                if (config('setting.commission_fee') == '2') {
                    if (empty($data['sell2'])) {
                        return callback(400, '请填写二级佣金');
                    }
                    $sell2 = $data['sell2'];
                }
                $arr1 = ['sell' => $sell, 'sell2' => $sell2];
            } elseif ($data['type'] == 6) {
                if (!$data['reward']) {
                    return callback(400, '请正确填写注册奖励');
                }
                $arr1 = $data['reward'];
            }
            $arr['rule'] = serialize($arr1);
            $arr['type'] = $data['type'];
            if ($id) {
                $i = Privilege::where('id', $id)->update($arr);
            } else {
                $i = Privilege::create($arr);
            }
            if ($i) {
                return callback(200, '成功');
            } else {
                return callback(400, '失败');
            }
        } else {
            $level = ResourceLevel::where('status', 1)->order('indexid asc')->select();
            $this->assign('levels', $level);
            if ($id) {
                $info = Privilege::where('id', $id)->find();
                $info->rule = unserialize($info->rule);
                if ($info->type == 3) {
                    $sort = ResourceSort::field('id,name')->where(['status'=> 1,'pid'=>0])->order('indexid asc')->select();
                    $this->assign('sort', $sort);
                    $info->rule = array_column($info->rule, null, 'id');
                }
                if ($info->type == 1) {
                    $info->rule = array_column($info->rule, null, 'level');
                }
                if ($info->type == 5) {
                    if (!is_array($info->rule)) {
                        $info->rule = ['sell' => $info->rule, 'sell2' => 0];
                    }
                }
                $this->assign('info', $info);
                return $this->fetch('editprivilege');
            } else {
                $sort = ResourceSort::field('id,name')->where(['status'=> 1,'pid'=>0])->order('indexid asc')->select();
                $this->assign('sort', $sort);
                return $this->fetch('addprivilege');
            }
        }
    }

    /**
     * 删除特权
     * @return [type] [description]
     */
    public function delprivilege($ids="")
    {
        if ($ids) {
            $this->model=new Privilege();
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    \app\common\model\SvipPrivilege::where('pid', $v['id'])->delete();
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($count) {
                return callback(200, '删除成功');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }
    /**
     * 设置用户SVIP等级
     * @return [type] [description]
     */
    public function setsvip()
    {
        $uid = input('ids/d', 0);
        if ($this->request->isPost()) {
            Db::startTrans();
            $data = $this->request->param('row/a');
            if (empty($data['id'])) {
                return callback(400, '请选择用户再操作');
            }
            if (empty($data['vid'])) {
                return callback(400, '请选择会员等级');
            }

            $svip = Svip::where('id', $data['vid'])->find();
            if (!$svip && ($data['vid'] != -1)) {
                return callback(400, '会员等级不存在');
            }
            $user = \app\common\model\User::where('id', $data['id'])->find();
            if (!$user) {
                return callback(400, '会员升级失败');
            }
            if ($data['vid'] == -1) {
                $exp_time = 0;
            } else {
                $exp_time = time() + ($svip->days * 86400);
            }
            $user->vid = ($data['vid'] == -1) ? 0 : $data['vid'];
            $user->exp_time = $exp_time;
            $user->utime = time();
            $res = $user->save();
            if (!$res) {
                Db::rollback();
                return callback(400, '会员升级失败');
            }
            #记录升级记录
            $data = [
                'type' => 3,//手动
                'admin_id' => $user->admin_id,
                'vid' => ($data['vid'] == -1) ? 0 : $data['vid'],
                'exp_time' => $exp_time,
                'uid' => $data['id'],
                'ctime' => time()
            ];
            $res = SvipLog::create($data);
            if (!$res) {
                Db::rollback();
                return callback(400, '会员升级失败');
            }
            Db::commit();
            return callback(200, 'SVIP升级成功');
        }
        $userSvip = \app\common\model\User::withJoin(['svip' => ['name', 'days', 'price', 'invite_num']])->where('user.id', $uid)->find();
        if (!empty($userSvip->exp_time)) {
            $userSvip->exp_time = date('Y-m-d H:i:s', $userSvip->exp_time);
        }
        $svipList = Svip::where('type', 0)->order('id asc')->select();
        $this->assign('row', $userSvip);
        $this->assign('sviplist', $svipList);
        return $this->fetch();
    }

    /**
     * 用户余额充值
     * @return void
     */
    public function recharge($ids = 0)
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->isAjax()) {
                try {
                    $data = $this->request->param('row/a');
                    if (empty($data['money'])) {
                        return callback(404, '请输入充值金额');
                    }
                    if ($data['money']<0) {
                        return callback(404, '请正确输入充值金额');
                    }
                    if (empty($data['remark'])) {
                        return callback(404, '请输入操作备注');
                    }
                    list($result, $info) = Bill::money($data['mode'], 4, $data['money'], $ids, $data['remark'], 0);
                    if (!$result) {
                        return callback(404, '充值失败:' . $info);
                    }
                    return callback(200, '充值成功');
                } catch (Exception $e) {
                    return callback(404, $e->getMessage());
                }
            }
            $user = $this->model->where('id', $ids)->find();
            $this->assign('user', $user);
            return $this->fetch();
        }
    }

    /**
     * svip升级记录
     * @return [type] [description]
     */
    public function usersvip()
    {
        $uid = input('ids');
        $type = input('type', 1);
        if (request()->isAjax()) {
            $data = input('post.');
            if ($type == 1) {//购买记录
                $list = Order::where('order.type', 2)->where('uid', $uid)
                    ->where('order.status', 1)
                    ->withJoin(['svip' => ['name']])
                    ->order('order.id desc')
                    ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                    ->toArray();
            } else {
                $list = SvipTask::where('uid', $uid)
                    ->withJoin(['svip' => ['name']])
                    ->order('id desc')
                    ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                    ->toArray();
            }
            return json(['code' => 0, 'msg' => '成功', 'count' => $list['total'], 'data' => $list['data']]);
        }
        return $this->fetch();
    }

    /**
     * 资源获取记录
     * @return [type] [description]
     */
    public function userResource()
    {
        $uid = input('get.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            $list = UserResource::where('user_resource.uid', $uid)
                ->withJoin(['spread' => ['title', 'price']])
                ->order('user_resource.id desc')
                ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                ->toArray();
            return json(['code' => 0, 'msg' => '成功', 'count' => $list['total'], 'data' => $list['data']]);
        }
        return $this->fetch();
    }

    /**
     * 余额变更记录
     * @return [type] [description]
     */
    public function bill()
    {
        $uid = input('get.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            $list = Bill::where('uid', $uid)
                ->order('id desc')
                ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                ->toArray();
            return json(['code' => 0, 'msg' => '成功', 'count' => $list['total'], 'data' => $list['data']]);
        }
        return $this->fetch();
    }
}