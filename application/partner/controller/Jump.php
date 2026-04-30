<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\common\model\Jump as JumpModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Jump extends Common
{

    protected $searchFields='jump.name';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='jump';
    protected $dataLimit='auth';
    protected $dataLimitField='uid';
    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new JumpModel();
    }
    /**
     * 跳转列表
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
                ->withJoin(['admin'=>['username','id']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['admin'=>['username','id']])
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
            $data=input('post.');
            $data['uid']=$this->admin_uid;
            if($this->model->save($data)){
                return callback(200,'成功');
            }else{
                return callback(400,'失败');
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $data=input('post.');
            $data['uid']=$this->admin_uid;
            if($this->model->where('id',$ids)->update($data)){
                return callback(200,'成功');
            }else{
                return callback(400,'失败');
            }
        }
        $info=$this->model->where('id',$ids)->find();
        $this->assign('info',$info);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if(request()->isAjax()){
            $data=input('post.');
            $arr=[];
            foreach ($data['data'] as $key => $value) {
                $arr[]=$value['id'];
            }
            $i=$this->model->destroy($arr);
            if($i){
                return callback(200,'成功');
            }else{
                return callback(400,'失败');
            }            
        }
    }
}