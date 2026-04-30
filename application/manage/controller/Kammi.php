<?php
namespace app\manage\controller;
use app\common\model\Kammi as kammiModel;
use app\common\model\Svip;

class Kammi extends Common{
	protected $searchFields='kammi.cdkey,svip.name,user.nickname,user.id,agent.username,agent.mobile';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='kammi';
    protected $dataLimit='auth';
    protected $relationSearch=true;
	public function initialize()
    {
        parent::initialize();
        $this->model = new kammiModel();
    }
	/**
	 * 卡密列表
	 * @return [type] [description]
	 */
	public function index(){
		$this->request->filter(['strip_tags']);
		if(request()->isAjax()){
			if($this->request->request('keyField')){
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model         
                ->withJoin(['user'=>['nickname'],'svip'=>['name'],'agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['user'=>['nickname'],'svip'=>['name'],'agent'=>['id','username']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
		}
		return view();
	}
    /**
     * 下发卡密
     */
    public function issued(){
        if(request()->isAjax()){
            $data=input('param.');
            $arr=[];
            for($i=1;$i<=$data['nums'];$i++){
                $arr[]=[
                    'admin_id'=>$data['admin_id'],
                    'vid'=>$data['vid'],
                    'money'=>$data['money'],
                    'cdkey'=>substr(strtoupper(md5(uniqid(rand(), true).$i)),8,16),
                    'utime'=>0,
                    'ctime'=>time(),
                ];
            }
            if($this->model->saveAll($arr)){
                return callback(200,'操作成功');
            }else{
                return callback(400,'操作失败');
            }
        }
        $agentList=\app\common\model\Agent::where(['status'=>1,'group_id'=>2])->order('id asc')->select();
        $svip=Svip::withJoin(['agent'=>['id','username']])->where(['svip.status'=>1])->field('svip.id,svip.name')->order('svip.id asc,svip.level asc')->select();
        $this->assign('svip',$svip);
        $this->assign('agentList',$agentList);
        return view();
    }
	/**
	 * 新增卡密
	 */
	public function add(){
		if(request()->isPost()){
			$data=input('param.');
			$arr=[];
			for($i=1;$i<=$data['nums'];$i++){
				$arr[]=[
                    'admin_id'=>$this->admin_uid,
                    'vid'=>$data['vid'],
					'money'=>$data['money'],
					'cdkey'=>substr(strtoupper(md5(uniqid(rand(), true).$i)),8,16),
					'utime'=>0,
					'ctime'=>time(),
				];
			}
			if($this->model->saveAll($arr)){
				return callback(200,'操作成功');
			}else{
				return callback(400,'操作失败');
			}
		}
        $svip=Svip::where(['admin_id'=>$this->admin_uid,'status'=>1])->field('id,name')->order('id asc,level asc')->select();
        $this->assign('svip',$svip);
        return view();
	}
}