<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\partner\model\PartnerLog;
use app\common\model\Admin;
use think\facade\Config;
use think\facade\Hook;
use think\Validate;
class Login extends Common
{

    protected $noNeedLogin = ['index','check','verify'];
    public function initialize()
    {
        parent::initialize();
    }
    public function index(){
        $createUrl = $this->request->get('url', createUrl('index/index'));
        if($this->auth->isLogin()){
            echo '<script>top.location.href="' . $createUrl . '";</script>';
            exit;
        }
        if ($this->request->isPost()) {
            $token = $this->request->post('__token__');
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $captcha =  $this->request->post('captcha');
            $keeplogin = $this->request->post('keeplogin');
            $rule = [
                '__token__' => 'require|token',
                'username'  => 'require|length:4,30',
                'password'  => 'require|length:6,30',
                'captcha'   => 'require|captcha',
            ];
            $data = [
                '__token__'  => $token,
                'username'  => $username,
                'password'  => $password,
                'captcha'   => $captcha,
            ];
            $validate = new Validate($rule, [], ['username' =>'请输入登录用户名', 'password' =>'请输入登录密码', 'captcha' =>'请输入验证码']);
            $result = $validate->check($data);
            if (!$result){
                return callback(400,$validate->getError());
            }
            PartnerLog::setTitle('登陆操作');
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true){
                return callback(200,'登录成功',$createUrl,['id' => $this->admin_uid, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : '用户名或密码错误';
                return callback(400,$msg);
            }
        }
        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()){
            $this->redirect($createUrl);
        }
        return $this->view->fetch();
    }
    public function verify()
    {
        $captcha = new \think\captcha\Captcha(['useCurve'=>false]);
        return $captcha->entry();
    }
    public function check($code){
       return captcha_check($code);
    }
}