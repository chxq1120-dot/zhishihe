<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

use app\common\model\AdminBill;
use app\common\model\AdminSite;
use app\common\model\Agent as agentModel;
use app\common\model\AuthGroup;
use app\common\model\Cash;
use app\common\model\SiteOrder;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use zp\Tree;

class Agent extends Common
{

    protected $searchFields = 'agent.username,agent.mobile,agent.id,groups.title';

    protected $modelValidate = true;

    protected $modelSceneValidate = true;

    protected $sceneTag = 'agent';

    protected $dataLimit = 'auth';

    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new agentModel();
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withSearch(['group_id'], ['group_id' => [2]])
                ->withJoin(['groups' => ['title'], 'agents' => ['username', 'id']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withSearch(['group_id'], ['group_id' => [2]])
                ->withJoin(['groups' => ['title'], 'agents' => ['username', 'id']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            foreach ($list as &$item){
                if($item['exp_time']>0){
                    $item['exp_time'] = date('Y-m-d', $item['exp_time']);
                }else{
                    $item['exp_time'] = '永久';
                }
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 分站申请订单
     */
    public function order()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new SiteOrder();
            $this->searchFields = 'site_order.ordno,agent.username,user.nickname';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['agent' => ['id', 'username'], 'user' => ['username', 'nickname'], 'svip' => ['name']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withJoin(['agent' => ['id', 'username'], 'user' => ['username', 'nickname'], 'svip' => ['name']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 设置分站
     */
    public function setting($ids = 0)
    {
        $row = AdminSite::where('admin_id', $ids)->find();
        if (!$row) {
            $admins = $this->model->get($ids);
            $row = [
                'id' => 0,
                'admin_id' => $ids,
                'webname' => '',
                'is_ssl' => 1,
                'sub_type' => 0,
                'sub_prefix' => '',
                'base_domain' => '',
                'domain' => '',
                'realname' => $admins->realname,
                'qrcode' => $admins->qrcode,
                'wechat' => $admins->wechat,
                'mobile' => $admins->mobile,
            ];
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    if (empty($params['is_ssl'])) {
                        $params['is_ssl'] = 0;
                    }
                    $siteModel = new AdminSite();
                    if (!empty($params['id'])) {
                        $result = $siteModel->allowField(true)->save($params, ['id' => $params['id']]);
                    } else {
                        $result = $siteModel->allowField(true)->save($params);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }
        $domain_list = [];
        $domains = config('setting.account_domains');
        if (!empty($domains)) {
            $domain_list = explode("\n", $domains);
        }
        $this->view->assign("domain_list", $domain_list);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 密码修改
     */
    public function pwd($ids = 0)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $agent = $this->model->where('id', $ids)->find();
                    if (!$agent) {
                        return callback(404, '没有权限操作');
                    }
                    if ($agent->group_id !== 2) {
                        return callback(404, '没有权限操作');
                    }
                    #获取此用户的父ID
                    $agent_list = $this->model->where(['status' => 1])->field('id,admin_id,username')->select();
                    if (!$agent_list) {
                        $agent_list = [];
                    } else {
                        $agent_list = $agent_list->toArray();
                    }
                    $agent_parent = Tree::instance()->init($agent_list, 'admin_id')->getParents($agent->id, true);
                    #获取某列数据
                    $agent_parent_ids = array_column($agent_parent, 'id');
                    if (!in_array($this->admin_uid, $agent_parent_ids)) {
                        return callback(404, '没有权限操作');
                    }
                    if ($params['password'] !== $params['epassword']) {
                        return callback(404, '二次密码输入错误');
                    }
                    $params['salt'] = mt_rand(111111, 999999);
                    $params['password'] = md5($params['password'] . $params['salt']);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '操作成功');
                } else {
                    return callback(404, '操作失败');
                }
            }
            return callback(404, '参数不能为空');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    $res = $this->model->where('username', $params['username'])->find();
                    if (!empty($res)) {
                        return callback(404, '用户名已经存在');
                    }
                    $min_pub = $this->auth->agent_min_pub;
                    if ($params['min_pub'] < $min_pub) {
                        return callback(404, '发布价格不能低于' . $min_pub . '元');
                    }
                    if(!empty($params['exp_time'])){
                        $params['exp_time']=strtotime($params['exp_time']);
                    }else{
                        $params['exp_time']=0;
                    }
                    $params['group_id'] = 2;
                    $params['salt'] = mt_rand(111111, 999999);
                    $params['password'] = md5($params['password'] . $params['salt']);
                    $agents = $this->model->create($params,true);
                    #是否同步推广资源
                    $auto_spread=intval($this->auth->auto_spread);
                    if($auto_spread==2){
                        $this->model->autoSpread($agents->id,$params['min_pub'],$params['dis_pub']);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $exp_date = date('Y-m-d',strtotime(' +1 year'));
        $this->view->assign('exp_date',$exp_date);
        return $this->view->fetch();
    }

    /**
     * 编辑个人资料
     */
    public function personal($ids = 0)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit' . $this->sceneTag : $name;

                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    $agent_id = $this->auth->id;
                    if (intval($agent_id) !== intval($ids)) {
                        return callback(404, '没有权限操作');
                    }
                    #判断修改密码
                    if (!empty($params['password'])) {
                        $params['salt'] = mt_rand(111111, 999999);
                        $params['password'] = md5($params['password'] . $params['salt']);
                    } else {
                        unset($params['password']);
                    }
                    $params['group_id'] = 2;
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    $agent = $this->model->where('id', $ids)->find();
                    if (!$agent) {
                        return callback(404, '没有权限操作');
                    }
                    if ($agent->group_id !== 2) {
                        return callback(404, '没有权限操作');
                    }
                    if(!empty($params['exp_time'])){
                        $params['exp_time']=strtotime($params['exp_time']);
                    }else{
                        $params['exp_time']=0;
                    }
                    unset($params['password']);
                    $params['group_id'] = 2;
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }
        if(!empty($row->exp_time)){
            $exp_date=date('Y-m-d',$row->exp_time);
        }else{
            $exp_date = date('Y-m-d',strtotime(' +100 year'));
        }
        $this->view->assign('exp_date',$exp_date);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 提现列表
     * @return [type] [description]
     */
    public function cash()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $this->searchvar($data);
            $list = Cash::withSearch(
                ['ctime', 'name', 'status'],
                ['ctime' => [$this->start, $this->end],
                    'name' => $this->search,
                    'status' => $this->status
                ])
                ->withJoin(['user' => ['nickname', 'avatar']])
                ->order('user_cash.id desc')
                ->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return $this->fetch();
    }

    /**
     * 余额明细列表
     * @return [type] [description]
     */
    public function fundlist()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new AdminBill();
            $this->dataLimitField = 'uid';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['agent' => ['id', 'username']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withJoin(['agent' => ['id', 'username']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 清空推广中心资源
     */
    public function cleanUp($ids = "")
    {
        if ($ids) {
            Db::startTrans();
            try {
                \app\common\model\Spread::where('admin_id','in', $ids)->delete();
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            return callback(200, '清除成功');
        }
        return callback(404, '参数ids不能为空');
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
                    #删除代理分站
                    \app\common\model\AdminSite::where('admin_id', $v['id'])->delete();
                    #删除代理推广资源
                    \app\common\model\Spread::where('admin_id', $v['id'])->delete();
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
                return callback(200, 'success');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }
}