<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\common\model\Admin;
use app\common\model\AdminBill;
use app\common\model\Bill;
use app\common\model\Cash as cashModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Cash extends Common
{

    protected $searchFields='cash.uid,cash.money,cash.remark';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='cash';
    protected $dataLimit='auth';
    protected $dataLimitField='uid';
    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new cashModel();
    }
    /**
     * 提现列表
     */
    public function index()
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
                ->withJoin(['user'=>['username','id']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['user'=>['username','id']])
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if($this->modelValidate){
                        $name =$this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add'.$this->sceneTag : $name;
                        $result=$this->validate($params,$validate);
                        if($result!==true){
                            return callback(404,$result);
                        }
                    }
                    if($params['type']==3){
                        if(empty($params['bank'])){
                            return callback(404, '请输入开户行');
                        }
                        if(empty($params['truename'])){
                            return callback(404, '请输入开户名');
                        }
                        if(empty($params['bankno'])){
                            return callback(404, '请输入银行账号');
                        }
                    }else{
                        if(empty($params['image'])){
                            return callback(404, '请上传收款码');
                        }
                    }
                    $user = Admin::where('id', $this->admin_uid)->find();
                    if ($params['money'] > $user->balance) {
                        return callback(404, '可提现余额不足');
                    }
                    #最低提现
                    $min_money = $user->min_cash;
                    if ($params['money'] < $min_money) {
                        return callback(404, '提现金额最低' . $min_money . '元');
                    }
                    #最高提现
                    $max_money = config('setting.agent_max_cash');
                    if ($params['money'] > $max_money) {
                        return callback(404, '提现单笔金额不超过' . $max_money . '元');
                    }
                    #提现时间
                    $cash_time = config('setting.agent_cash_time');
                    $cashTime = explode('-', $cash_time);
                    $time = time();
                    if ($time < strtotime(date('Y-m-d ' . $cashTime[0])) || $time > strtotime(date('Y-m-d ' . $cashTime[1]))) {
                        return callback(404, '每日提现时间' . $cash_time);
                    }
                    #提现次数
                    $cash_num = config('setting.agent_cash_num');
                    $count = $this->model->where(['uid' => $this->admin_uid])
                        ->where('ctime', 'between time', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
                        ->count();
                    if ($count >= $cash_num) {
                        return callback(404, '每日只能提现' . $cash_num . '次');
                    }
                    $fee = bcdiv(bcmul($params['money'], $user->cash_fee, 2), 100, 2);
                    $amount = bcsub($params['money'], $fee, 2);
                    if ($amount !== $params['amount']) {
                        return callback(404, '提现金额不正确');
                    }
                    $params['fee'] =$fee;
                    $params['pid'] =$this->auth->admin_id;
                    $params['uid'] = $this->admin_uid;
                    $params['ctime'] = time();
                    $result = $this->model->create($params,true);
                    if (!$result) {
                        return callback(404, '提现申请提交失败');
                    }
                    #写入余额记录
                    $remark = '【'.$user->username.'】代理提现' . $params['money'] . '元，手续费' . $fee . '元，实际到账' . $amount . '元';
                    list($res, $info) = AdminBill::money(2,5,$params['money'], $this->admin_uid, $remark,$result->id);
                    if (!$res) {
                        Db::rollback();
                        return callback(404, '提现申请提交失败');
                    }
                    Db::commit();
                    return callback(200, '提现申请提交成功');
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                }
            }
            return callback(404,'参数丢失');
        }
        $user=Admin::where('id',$this->admin_uid)->find();
        $this->view->assign('user',$user);

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404,'数据不存在');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField],$adminIds)){
                return callback(404,'您没有权限');
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if($this->modelValidate){
                        $name =$this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit'.$this->sceneTag : $name;
                        $result=$this->validate($params,$validate);
                        if($result!==true){
                            return callback(404,$result);
                        }
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                }
                if ($result !== false) {
                    return callback(200,'success');
                } else {
                    return callback(404,'数据更新失败');
                }
            }
            return callback(404,'参数不能为空');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}