<?php
namespace app\manage\controller;
use app\common\model\Adv as AdvModel;
use app\common\model\AdvSet;
class Adv extends Common{
    protected $searchFields='name';
    protected $modelValidate=false;
    protected $modelSceneValidate=false;
    protected $sceneTag='adv';
    protected $dataLimit='auth';
    protected $dataLimitField='uid';
    protected $relationSearch=false;

    public function initialize(){
        parent::initialize();
        $this->model=new AdvModel();
    }
    /**
     * 广告位管理
     */
    public function adsense()
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
                
                ->order($sort, $order)
                ->count();

            $list = $this->model
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
     * 新增广告位
     * @return [type] [description]
     */
    public function saveadv(){
        $id=input('get.ids');
        if(request()->isAjax()){
            $data=input('post.');
            if($id){
                $i=AdvModel::where('id',$id)->update($data);
            }else{
                $i=AdvModel::create($data);
            }
            if($i){
                return callback(200,'成功');
            }else{
                return callback(400,'失败');
            }
        }else{
            $info=AdvModel::where('id',$id)->find();
            $this->assign('info',$info);
            return $this->fetch();
        }

    }
    /**
     * 广告位设置
     * @return [type] [description]
     */
    public function advset(){
        if(request()->isPost()){
            $data=input('post.');
            foreach($data as $key=>$value){
                if($key=='ban'){
                    $type=1;
                }elseif($key=='plug'){
                    $type=3;
                }elseif($key=='video'){
                    $type=4;
                }elseif($key=='cell'){
                    $type=5;
                }elseif($key=='nat'){
                    $type=7;
                }
                foreach($value as $k=>$v){
                    $info=AdvSet::where('adsense',$k)->where('type',$type)->find();
                    if($info){
                        $info->aid=$v;
                        $info->save();
                    }else{
                        AdvSet::create(['aid'=>$v,'adsense'=>$k,'type'=>$type]);
                    }
                }
            }
            return callback(200,'成功');
        }else{
            $ban=AdvModel::where('status',1)->where('type',1)->select();
            $this->assign('ban',$ban);

            $plug=AdvModel::where('status',1)->where('type',3)->select();
            $this->assign('plug',$plug);

            $video=AdvModel::where('status',1)->where('type',4)->select();
            $this->assign('video',$video);

            $cell=AdvModel::where('status',1)->where('type',5)->select();
            $this->assign('cell',$cell);

            $nat=AdvModel::where('status',1)->where('type',7)->select();
            $this->assign('nat',$nat);

            return $this->fetch();
        }

    }
}