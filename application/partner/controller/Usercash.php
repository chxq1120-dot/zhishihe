<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

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

}