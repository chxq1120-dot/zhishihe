<?php

namespace app\api\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\Invite;
use app\common\model\Spread;
use app\common\model\UserSubscribe;
use app\common\model\UserThird;
use Naixiaoxin\ThinkWechat\Facade;
use app\common\model\{User, ResourceTask};
use app\api\validate\Check;
use app\common\model\Validate;

class Login extends Common
{
    /**
     * 提交jscode
     * @return [type] [description]
     */
    public function subcode()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Login.subcode')->check($data)) {
                $this->error($validate->getError());
            }
            $type = $this->request->param('type/d', 0);
            switch ($type) {
                case 1:#抖音头条
                    $miniProgram = new Toutiao();
                    $info = $miniProgram->getOpenId($data['code']);
                    if (isset($info['openid'])) {
                        $this->success('success', $info);
                    } else {
                        $this->error('获取失败');
                    }
                    break;
                default:#默认微信
                    $miniProgram = Facade::miniProgram();
                    $info = $miniProgram->auth->session($data['code']);
                    if (isset($info['openid'])) {
                        $this->success('success', $info);
                    } else {
                        $this->error('获取失败');
                    }
            }
        }
    }

    /**
     * 登录
     * @return [type] [description]
     */
    public function login()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $validate = new Check;
            if (!$validate->scene('Login.login')->check($data)) {
                $this->error($validate->getError());
            }
            #代理ID
            if (empty($data['from_id'])) {
                $data['from_id'] = (new Admin())->getDefaultAdminId();
            }
            #资源ID
            if (empty($data['rid'])) {
                $data['rid'] = 0;
            }
            #邀请人ID
            if (empty($data['invite_uid'])) {
                $data['invite_uid'] = 0;
            }
            $userThird = new UserThird();
            #获取第三方保存的登录ID
            $user_third_id = $userThird->saveThird($data, 1);
            $data['user_third_id'] = $user_third_id;
            #写入登录或注册信息
            list($res, $info) = $userThird->handleThird($data, 1);
            if (!$res) {
                $this->error($info);
            }
            $this->success('success', $info);
        }
    }

    /**
     * APP登录
     * @return [type] [description]
     */
    public function loginApp()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $validate = new Check;
            if (!$validate->scene('Login.login')->check($data)) {
                $this->error($validate->getError());
            }
            #代理ID
            if (empty($data['from_id'])) {
                $data['from_id'] = (new Admin())->getDefaultAdminId();
            }
            #资源ID
            if (empty($data['rid'])) {
                $data['rid'] = 0;
            }
            #邀请人ID
            if (empty($data['invite_uid'])) {
                $data['invite_uid'] = 0;
            }
            $userThird = new UserThird();
            #获取第三方保存的登录ID
            $user_third_id = $userThird->saveThird($data, 5);
            $data['user_third_id'] = $user_third_id;
            #写入登录或注册信息
            list($res, $info) = $userThird->handleThird($data, 5);
            if (!$res) {
                $this->error($info);
            }
            $this->success('success', $info);
        }
    }

    /**
     * 自动注册登录
     * @return [type] [description]
     */
    public function autoregister()
    {
        if($this->request->isPost()){
            $data = input('param.');
            $username = rand_string(2, 3) . mt_rand(11111111, 99999999);
            $arr['username'] = $username;
            $arr['salt'] = mt_rand(100001, 999999);
            $arr['pwd'] = md5($username . $arr['salt']);
            $arr['admin_id'] = empty($data['from_id']) ? (new Admin())->getDefaultAdminId() : $data['from_id'];
            $arr['pid'] = empty($data['invite_uid']) ? 0 : $data['invite_uid'];
            $arr['token'] = md5(time() . mt_rand(1000, 9999));
            $arr['avatar'] = config('setting.web_logo');
            $arr['nickname'] = '用户' . rand_string(6, 0);
            #代理ID
            $info = User::create($arr, true);
            if (!$info) {
                $this->error('注册失败1');
            }
            #写入助力记录
            if ($arr['pid']) {
                $invite = [
                    'admin_id' => $data['from_id'],
                    'rid' => $data['rid'],
                    'pid' => $arr['pid'],
                    'uid' => $info->id,
                    'ctime' => time()
                ];
                Invite::create($invite, true);
                if (!empty($data['rid'])) {
                    #更新资源邀请任务信息
                    $task = ResourceTask::where('uid', $arr['pid'])->where('rid', $data['rid'])->find();
                    if ($task) {
                        $spread = Spread::where('id', $data['rid'])->find();
                        if ($task->invite_num >= $spread->invite_num && ($task->is_video == 1 || $spread->exc_video == 0)) {
                            $task->status = 1;
                        }
                        $task->invite_num = ['inc', 1];
                        $task->utime = time();
                        $task->save();
                    }
                }
            }
            unset($info['pwd']);
            unset($info['salt']);
            $this->success('success', $info);
        }
    }

    /**
     * 注册
     * @return [type] [description]
     */
    public function register()
    {
        $data = input('param.');
        $validate = new Check;
        if (!$validate->scene('Login.register')->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['pwd'] !== $data['rpwd']) {
            $this->error('二次密码输入不一致');
        }
        $user = User::where('username|mobile', '=', $data['mobile'])->find();
        if ($user) {
            $this->error('手机号码已注册过');
        }
        $code = Validate::where(['mobile' => $data['mobile'], 'status' => 0])->order('id desc')->find();
        if (!$code) {
            $this->error('验证码错误');
        }
        if ((time() - 5 * 60) > strtotime($code->ctime)) {
            $this->error('验证码已过期');
        }
        if ($code->code != $data['code']) {
            $this->error('验证码输入错误');
        }
        $code->status = 1;
        if (!$code->save()) {
            $this->error('注册失败');
        }
        $arr['username'] = $data['mobile'];
        $arr['mobile'] = $data['mobile'];
        $arr['salt'] = mt_rand(100001, 999999);
        $arr['pwd'] = md5($data['pwd'] . $arr['salt']);
        $arr['admin_id'] = $data['from_id'] ?? 1;
        $arr['pid'] = $data['invite_uid'] ?? 0;
        $arr['token'] = md5(time() . mt_rand(1000, 9999));
        $arr['avatar'] = config('setting.web_logo');
        $arr['nickname'] = '用户' . rand_string(6, 0);
        #代理ID
        $info = User::create($arr, true);
        if (!$info) {
            $this->error('注册失败1');
        }
        #写入助力记录
        if ($arr['pid']) {
            $invite = [
                'admin_id' => $data['from_id'],
                'rid' => $data['rid'],
                'pid' => $arr['pid'],
                'uid' => $info->id,
                'ctime' => time()
            ];
            Invite::create($invite, true);
            if (!empty($data['rid'])) {
                #更新资源邀请任务信息
                $task = ResourceTask::where('uid', $arr['pid'])->where('rid', $data['rid'])->find();
                if ($task) {
                    $spread = Spread::where('id', $data['rid'])->find();
                    if ($task->invite_num >= $spread->invite_num && ($task->is_video == 1 || $spread->exc_video == 0)) {
                        $task->status = 1;
                    }
                    $task->invite_num = ['inc', 1];
                    $task->utime = time();
                    $task->save();
                }
            }
        }
        unset($info['pwd']);
        unset($info['salt']);
        $this->success('success', $info);
    }

    /**
     * 授权登录
     * @return [type] [description]
     */
    public function oauth()
    {
        $data = input('param.');
        $validate = new Check;
        if (!$validate->scene('Login.oauth')->check($data)) {
            $this->error($validate->getError());
        }
        $app = Facade::officialAccount();
        $acc_token = $app->oauth->getAccessToken($data['code']);
        $user = $app->oauth->user($acc_token);
        $unionid = !empty($user->getOriginal()['unionid']) ? $user->getOriginal()['unionid'] : '';
        $data['openid'] = $user->getId();
        $data['avatar'] = $user->getAvatar();
        $data['nickname'] = $user->getName();
        $data['sex'] = !empty($user->getOriginal()['sex']) ? $user->getOriginal()['sex'] : '0';
        $data['unionid'] = $unionid;
        #代理ID
        if (empty($data['from_id'])) {
            $data['from_id'] = (new Admin())->getDefaultAdminId();
        }
        #资源ID
        if (empty($data['rid'])) {
            $data['rid'] = 0;
        }
        #邀请人ID
        if (empty($data['invite_uid'])) {
            $data['invite_uid'] = 0;
        }
        $userThird = new UserThird();
        #获取第三方保存的登录ID
        $user_third_id = $userThird->saveThird($data, 2);
        $data['user_third_id'] = $user_third_id;
        #写入登录或注册信息
        list($res, $info) = $userThird->handleThird($data, 2);
        if (!$res) {
            $this->error($info);
        }
        $this->success('success', $info);
    }

    /**
     * web页登录
     * @return [type] [description]
     */
    public function weblogin()
    {
        if ($this->request->isPost()) {
            $data = input('param.');
            $validate = new Check;
            if (!$validate->scene('Login.weblogin')->check($data)) {
                $this->error($validate->getError());
            }
            $user = User::where('username', $data['username'])->find();
            if (!$user) {
                $this->error('登录账号输入错误');
            }
            $pwd = md5($data['pwd'] . $user->salt);
            if ($user->pwd !== $pwd) {
                $this->error('登录密码输入错误');
            }
            if ($user->status == 0) {
                $this->error('登录账号已禁用');
            }
            $user->token = md5(time() . mt_rand(1000, 9999));
            $user->utime = time();
            $user->save();
            unset($user['pwd']);
            unset($user['salt']);
            $this->success('success', $user);
        }
    }

    /**
     * 重置密码
     * @return [type] [description]
     */
    public function resetpwd()
    {
        $data = input('param.');
        $validate = new Check;
        if (!$validate->scene('Login.resetpwd')->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['pwd'] != $data['rpwd']) {
            $this->error('二次密码输入不一致');
        }
        $user = User::where('username', $data['mobile'])->find();
        if (!$user) {
            $this->error('账号不存在');
        }
        $code = Validate::where('mobile', $data['mobile'])->order('id desc')->where('status', 0)->find();
        if (!$code) {
            $this->error('验证码错误');
        }
        if ((time() - 5 * 60) > strtotime($code->ctime)) {
            $this->error('验证码已过期');
        }
        if ($code->code != $data['code']) {
            $this->error('验证码错误');
        }
        $code->status = 1;
        if (!$code->save()) {
            $this->error('操作失败');
        }
        $user->pwd = md5($data['pwd'] . $user->salt);
        $user->utime = time();
        if (!$user->save()) {
            $this->error('操作失败1');
        }
        $this->success('success');
    }

    /**
     * 字节登录
     * @return [type] [description]
     */
    public function toutiaologin()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $validate = new Check;
            if (!$validate->scene('Login.toutiaologin')->check($data)) {
                $this->error($validate->getError());
            }
            #代理ID
            if (empty($data['from_id'])) {
                $data['from_id'] = (new Admin())->getDefaultAdminId();
            }
            #资源ID
            if (empty($data['rid'])) {
                $data['rid'] = 0;
            }
            #邀请人ID
            if (empty($data['invite_uid'])) {
                $data['invite_uid'] = 0;
            }
            $userThird = new UserThird();
            #获取第三方保存的登录ID
            $user_third_id = $userThird->saveThird($data, 3);
            $data['user_third_id'] = $user_third_id;
            #写入登录或注册信息
            list($res, $userInfo) = $userThird->handleThird($data, 3);
            if (!$res) {
                $this->error($userInfo);
            }
            $this->success('success', $userInfo);
        }
    }

    /**
     * 注册以及第三方绑定手机号码
     */
    public function smslogin()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $validate = new Check;
            if (!$validate->scene('Login.smslogin')->check($data)) {
                $this->error($validate->getError());
            }
            $code = Validate::where(['mobile' => $data['mobile'], 'status' => 0])->order('id desc')->find();
            if (!$code) {
                $this->error('验证码错误');
            }
            if ((time() - 5 * 60) > strtotime($code->ctime)) {
                $this->error('验证码已过期');
            }
            if ($code->code !== $data['code']) {
                $this->error('验证码输入错误');
            }
            $code->status = 1;
            if (!$code->save()) {
                $this->error('手机号码绑定失败');
            }
            #单独绑定手机号码（含换绑）
            if (!empty($data['token']) && empty($data['user_third_id'])) {
                $user = User::where(['token'=>$data['token'],'status'=>1])->find();
                if ($user) {
                    if ($user->username == $data['mobile']) {
                        $this->error('请输入新手机号码进行绑定');
                    }
                    #判断新手机号码是在其他账号上是否绑定过
                    $user_oid = User::where('username', $data['mobile'])->where('id', '<>', $user->id)->value('id');
                    if (!empty($user_oid)) {
                        $this->error('手机号码已经绑定过了，请换一个');
                    }
                    $user->username = $data['mobile'];
                    $user->mobile = $data['mobile'];
                    $user->utime = time();
                    $result = $user->save();
                    if (!$result) {
                        $this->error('手机号码绑定失败');
                    }
                    $this->success('success');
                }
                $this->error('手机号码绑定失败，请重新登录');
            }
            #代理ID
            $data['admin_id'] = !empty($data['from_id']) ? $data['from_id'] : (new Admin())->getDefaultAdminId();
            #资源ID
            if (empty($data['rid'])) {
                $data['rid'] = 0;
            }
            #邀请人ID
            $data['pid'] = 0;
            if (!empty($data['invite_uid'])) {
                $data['pid'] = $data['invite_uid'];
            }
            #openid
            if (empty($data['openid'])) {
                $data['openid'] = '';
            }
            list($res, $user) = (new User())->smsLogin($data);
            if (!$res) {
                $this->error($user);
            }
            $this->success('success', $user);
        }
    }
}