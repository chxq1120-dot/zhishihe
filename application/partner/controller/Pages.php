<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\common\model\Pages as pagesModel;
class Pages extends Common
{
    protected $searchFields='name';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='pages';

    public function initialize(){
        parent::initialize();
        $this->model=new pagesModel();
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
            if($this->request->request('keyField')){
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type',3)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type',3)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        return $this->view->fetch();
    }

}