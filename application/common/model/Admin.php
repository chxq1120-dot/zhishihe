<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\common\model;
use app\common\model\Common;
use think\Validate;
use think\facade\Session;
class Admin extends Common
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'logtime_text',
    ];
    public function initialize(){
        parent::initialize();
    }
    protected $rule = [
        'username' => 'require|length:3,20|alphaDash',
        'pwd' => 'length:8,20',
        'mobile' => ['regex' => '^1[3|4|5|6|7|8][0-9]\d{4,8}$'],
        'email' => 'email',
    ];
    protected $msg = [
        'username.require' => '请输入用户名',
        'username.length' => '用户名长度6~20位',
        'username.alphaDash' => '用户名只能是字母、数字或下划线组成',
        'pwd.length' => '密码长度8~20位',
        'mobile.regex' => '请正确输入手机号码',
        'email.email' => '请正确输入电子邮箱',
    ];
    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //获取器
    public function getLogtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logtime']) ? $data['logtime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }
    /**
     * 登录验证
     * @param $data
     * @return bool
     */
    public function login($data){
        $user=$this->with('groups')->where('username',$data['username'])->find();
        if($user){
            if($user['pwd'] == $this->md5Pwd($data['password'],$user['salt'])){
                session('manage_id',$user['id']);
                session('username',$user['username']);
                Session::set('admin',$user->toArray());
                return true;
            }
        }
        return false;
    }
    /**
     * 获取数据
     * @param $params
     */
    public function getList($params){
        $list = $this->alias('a')
            ->join($this->prefix.'auth_group b','a.group_id=b.id')
            ->field('a.*,b.title')
            ->where($params['where'])
            ->order($params['order'])
            ->select();
        return $list;
    }

    /**
     * 添加或修改
     * @param $params
     */
    public function addOrUpdate($params){
        $params['status']=1;
        $params['ip']=request()->ip();
        #判断是新增还是修改
        if(!empty($params['id'])){
            $admin = $this->where(['id'=>$params['id']])->find();
            if(!$admin){
                return [false,'操作错误'];
            }
            if(empty($params['password'])){
                unset($params['password']);
            }else{
                $params['password'] = $this->md5Pwd($params['password'],$admin->salt);
            }
            unset($params['username']);//不允许修改用户名
            #更新数据库
            $this->allowField(true)->save($params,['id'=>$params['id']]);
        }else{
            //校验数据
            $validate = new Validate($this->rule, $this->msg);
            if(!$validate->check($params)){
                return [false,$validate->getError()];
            }
            #判断用户名是否重复
            $admin = $this->where(['username'=>$params['username']])->find();
            if($admin){
                return [false,'用户名已经存在'];
            }
            $params['ctime'] = time();
            if(!isset($params['password']) && $params['password'] == ""){
                return [false,'密码不能为空'];
            }
            $params['salt']=mt_rand(111111,999999);
            $params['password'] = $this->md5Pwd($params['password'], $params['salt']);
            #写入数据库
            $this->data($params)->allowField(true)->save();
        }
        return [true,'添加成功'];
    }
     /*
     * 获取默认站点管理员ID
     */
    public function getDefaultAdminId()
    {
        $adminId = $this->where(['group_id' => 1,'status'=>1])->order('id asc')->value('id');
        if (empty($adminId)) {
            $adminId = 1;
        }
        return $adminId;
    }
    /**
     * 加密密码
     */
    public function md5Pwd($pwd,$salt){
        return md5($pwd.$salt);
    }

    public function groups()
    {
        return $this->belongsTo('AuthGroup', 'group_id')->setEagerlyType(0);
    }
}