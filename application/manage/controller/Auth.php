<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\manage\controller;
use app\common\model\AdminBill;
use app\common\model\Short;
use think\Db;
use think\Exception;
use zp\Leftnav;
use app\common\model\Admin;
use app\common\model\AuthGroup;
use app\common\model\AuthRule;
use zp\Tree;

class Auth extends Common
{
    public function initialize()
    {
        parent::initialize();
        $this->model = new AuthRule();
    }
    //管理员列表
    public function adminList(){
        if(request()->isAjax()){
            $admin=new Admin();
            $map=[];
            if(!$this->auth->isSuperAdmin()){
                $map[]=['a.id','=',$this->admin_uid];
            }else{
                $map[]=['a.group_id','<>',2];
            }
            $list=$admin->getList(['where'=>$map,'order'=>'a.id asc']);
            return callback(200,'success','',$list);
        }
        return $this->fetch('adminlist');
    }
    /**
     * 添加管理
     * @return array
     */
    public function adminAdd(){
        if(request()->isPost()){
            $data = $this->request->param('row/a');
            if(empty($data['username'])){
                return callback(400,'请输入登录用户名');
            }
            if(empty($data['password'])){
                return callback(400,'请输入登录密码');
            }
            if(empty($data['group_id'])){
                return callback(400,'请选择所属用户组');
            }
            $admin=new Admin();
            list($res,$msg)=$admin->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'添加成功',createUrl('auth/adminlist'));
        }
        $groups=AuthGroup::where('id','<>',2)->select();
        $this->assign('groups',$groups);
        $this->assign('title',lang('add').lang('admin'));
        $this->assign('info',json_encode(['group_id'=>0]));
        return view('adminadd');
    }
    //更新管理员信息
    public function adminEdit($ids=0){
        if(request()->isPost()){
            $data = $this->request->param('row/a');
            $admin=new Admin();
            list($res,$msg)=$admin->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'更新成功',createUrl('auth/adminlist'));
        }
        $groups = AuthGroup::where('id','<>',2)->select();
        $info = Admin::where('id',$ids)->find();
        $this->assign('row',$info);
        $this->assign('groups',$groups);
        $this->assign('title',lang('edit').lang('admin'));
        return view('adminedit');
    }

    //删除管理员
    public function adminDel(){
        $admin_id=input('post.id/d');
        $admin=new Admin();
        if($this->auth->isSuperAdmin()){
            $admin::destroy(['id'=>$admin_id]);
            return callback(200,'删除成功!');
        }else{
            return callback(400,'您没有删除管理员的权限');
        }
    }
    //修改数据权限
    public function setAuth(){
        $id=input('post.id');
        if(empty($id)){
            return callback(400,'操作错误');
        }
        $group=AuthGroup::where('id',$id)->find();//判断当前状态情况
        if($group){
            $group->is_auth=input('post.is_auth');
            $res=$group->save();
            if(!$res){
                return callback(404,'设置错误');
            }
            return callback(200,'sucess');
        }
        return callback(400,'操作错误');
    }
    //修改管理员状态
    public function adminState(){
        $id=input('post.id');
        if(empty($id)){
            return callback(400,'操作错误');
        }
        $admin=Admin::where('id',$id)->find();//判断当前状态情况
        if($admin){
            if($admin->status==1){
                $admin->status=0;
                $admin->save();
                return callback(200,'sucess','',['status'=>0]);
            }else{
                $admin->status=1;
                $admin->save();
                return callback(200,'sucess','',['status'=>1]);
            }
        }
        return callback(400,'操作错误');
    }

    /*-----------------------用户组管理----------------------*/
    //用户组管理
    public function adminGroup(){
        if(request()->isAjax()){
            $list = AuthGroup::select();
            foreach($list as $k =>$v){
                $list[$k]['ctime']=date('Y-m-d H:i:s',$v['ctime']);
            }
            return callback(200,'success','',$list);
        }
        return $this->fetch('admingroup');
    }
    //删除管理员分组
    public function groupDel(){
        $res=AuthGroup::destroy(['id'=>input('id/d')]);
        if(!$res){
            return callback(400,'删除失败');
        }
        return callback(200,'删除成功');
    }
    //添加分组
    public function groupAdd(){
        if(request()->isPost()){
            $data=input('post.');
            $group=new AuthGroup();
            list($res,$msg)=$group->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'添加成功',createUrl('auth/admingroup'));
        }else{
            $this->assign('title','添加用户组');
            $this->assign('info','null');
            return $this->fetch('groupform');
        }
    }
    //修改分组
    public function groupEdit(){
        if(request()->isPost()) {
            $data=input('post.');
            $group=new AuthGroup();
            list($res,$msg)=$group->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'更新成功',createUrl('auth/admingroup'));
        }else{
            $id = input('id/d',0);
            $info = AuthGroup::get(['id'=>$id]);
            $this->assign('info', json_encode($info,true));
            $this->assign('title','编辑用户组');
            return $this->fetch('groupform');
        }
    }
    //分组配置规则
    public function groupAccess(){
        $group_id=input('id/d',0);
        if(empty($group_id)){
            return callback(400,'操作错误');
        }
        $nav = new Leftnav();
        $admin_rule=AuthRule::field('id,pid,title')->order('sort asc')->select();
        $groups = AuthGroup::where('id',$group_id)->field('id,title,rules')->find();
        $arr = $nav->auth($admin_rule,$pid=0,$groups->rules);
        $this->assign('title',$groups->title);
        $this->assign('rules',$groups->rules);
        $this->assign('id',$groups->id);
        $this->assign('data',json_encode($arr,true));
        return $this->fetch('groupaccess');
    }
    public function groupSetAccess(){
        $rules = input('post.rules');
        $id=input('post.id/d');
        if(empty($id)){
            return callback(400,'操作错误1');
        }
        if(empty($rules)){
            return callback(400,'请选择权限');
        }
        $rules=implode(',',$rules);
        $res=AuthGroup::where('id',$id)->update(['rules'=>$rules]);
        if($res){
            return callback(200,'权限配置成功',createUrl('admingroup'));
        }else{
            return callback(400,'操作错误2');
        }
    }

    /********************************权限管理*******************************/
    public function adminRule(){
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if (request()->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->relationSearch = false;
            $this->dataLimit = false;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = AuthRule::where($where)
                ->order($sort, $order)
                ->count();

            $list = AuthRule::where($where)
                ->order('sort', 'asc')
                ->limit($offset, $limit)
                ->select();

            $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->fetch('adminrule');
    }

    public function ruleAdd(){
        if(request()->isPost()){
            $data = input('post.');
            $rule=new AuthRule();
            list($res,$msg)=$rule->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            cache('rulemenu',null);
            cache('rulelist',null);
            return callback(200,'权限添加成功',createUrl('ruleadd'));
        }else{
            $rule=new AuthRule();
            $nav = new Leftnav();
            $arr = cache('rulemenu');
            if(!$arr){
                $authRule = $rule->order('sort asc')->select();
                $arr = $nav->menu($authRule);
                cache('rulemenu', $arr, 3600);
            }
            $this->assign('rule_menu',$arr);//权限列表
            return $this->fetch('ruleadd');
        }
    }
    public function ruleEdit(){
        if(request()->isPost()) {
            $data = input('post.');
            $rule=new AuthRule();
            list($res,$msg)=$rule->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            cache('rulemenu',null);
            cache('rulelist',null);
            return callback(200,'保存成功',createUrl('adminrule'));
        }else{
            $id=input('id/d',0);
            $rule = authRule::where('id',$id)->find();
            $sort = authRule::order('sort asc')->select();
            $tree_list = [];
            if ($sort) {
                $sort_list = $sort->toArray();
                $tree = new Tree();
                $tree->init($sort_list, 'pid');
                $tree_list = $tree->getTree(0, '<option value=@id @selected @disabled>@spacer@title</option>', $rule->pid, $id);
            }
            $this->assign('sortSelect', $tree_list);
            $this->assign('row',$rule);
            return $this->fetch('ruleedit');
        }
    }
    public function ruleDel(){
        $id=input('param.id/d',0);
        $res=authRule::destroy($id);
        if(!$res){
            return callback(400,'删除失败');
        }
        cache('rulemenu',null);
        cache('rulelist',null);
        return callback(200,'删除成功');
    }
}