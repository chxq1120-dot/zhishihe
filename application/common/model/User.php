<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\model;

use Naixiaoxin\ThinkWechat\Facade;
use think\Model;

class User extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'login_time_text'
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //获取器
    public function getLoginTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['login_time']) ? $data['login_time'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }

    public function svip()
    {
        return $this->belongsTo('Svip', 'vid', 'id')->setEagerlyType(0)->joinType('left');
    }

    public function agent()
    {
        return $this->belongsTo('Agent', 'admin_id', 'id')->setEagerlyType(0)->joinType('left');
    }

    //邀请人
    public function invite()
    {
        return $this->belongsTo('User', 'pid', 'id')->setEagerlyType(0)->joinType('left');
    }

    public function province()
    {
        return $this->belongsTo('City', 'province_id', 'code')->setEagerlyType(0);
    }

    public function city()
    {
        return $this->belongsTo('City', 'city_id', 'code')->setEagerlyType(0);
    }

    public function county()
    {
        return $this->belongsTo('City', 'county_id', 'code')->setEagerlyType(0);
    }

    /**
     * 登录验证
     * @param $data
     * @return array
     */
    public function login($data)
    {
        $user = $this->where('username', $data['username'])->withJoin(['province' => ['name'], 'city' => ['name'], 'county' => ['name']], 'left')->find();
        if (!empty($user)) {
            if ($user['pwd'] == md5($data['pwd'] . $user['salt'])) {
                $login_time = time();
                $user->token = md5(md5($data['username'] . '@' . $user->id) . $login_time);
                $user->avatar = $data['avatar'];
                $user->openid = $data['openid'];
                $user->nickname = $data['nickname'];
                $user->login_time = $login_time;
                $user->save();
                unset($user['pwd']);
                unset($user['salt']);
                $user = $user->toArray();
                $user['group_id'] = Admin::where(['id' => $user['admin_id']])->value('group_id');
                return [true, $user];
            }
        }
        return [false, '用户名或密码错误'];
    }

    /**
     * 检测手机号码是否重复
     */
    protected function checkByMobile($mobile)
    {
        $user = $this->where('username', $mobile)->find();
        if ($user) {
            return true;
        }
        return false;
    }

    /**
     * 加密密码
     * @param $pwd
     * @param $salt
     */
    protected function enPassword($pwd, $salt)
    {
        return md5($pwd . $salt);
    }

    /**
     * 创建用户
     */
    public function createUser($data)
    {
        if (isset($data['mobile'])) {
            if (empty($data['mobile'])) {
                return [false, '手机号不能为空'];
            }
            if (!is_mobile_phone($data['mobile'])) {
                return [false, '请输入正确的手机号'];
            }
            $flag = $this->checkByMobile($data['mobile']);
            if ($flag) {
                return [false, '手机号已经存在'];
            }
        }
        if (isset($data['pwd'])) {
            if ($data['pwd'] == '' || strlen($data['pwd']) < 6 || strlen($data['pwd']) > 32) {
                return [false, '请输入正确的手机号'];
            }
            //密码效验
            if ($data['pwd'] !== $data['rpwd']) {
                return [false, '两次输入的密码不一致'];
            }
        }
        $time = time();
        $salt = mt_rand(111111, 999999);
        $newUser['pid'] = isset($data['pid']) ? $data['pid'] : 0;
        $newUser['admin_id'] = isset($data['admin_id']) ? $data['admin_id'] : 0;
        $newUser['username'] = isset($data['mobile']) ? $data['mobile'] : "";
        $newUser['mobile'] = isset($data['mobile']) ? $data['mobile'] : "";
        $newUser['salt'] = $salt;
        $newUser['pwd'] = isset($data['pwd']) ? $this->enPassword($data['pwd'], $salt) : "";
        $newUser['avatar'] = isset($data['avatar']) ? $data['avatar'] : config('setting.web_logo');
        $newUser['nickname'] = empty($data['nickname']) ? '用户' . rand_string(6, 0) : $data['nickname'];
        $newUser['ctime'] = $time;
        $newUser['utime'] = $time;
        $newUser['status'] = 1;
        $user = $this->create($newUser, true);
        if (!$user) {
            return [false, '用户创建失败'];
        }
        unset($user->salt);
        unset($user->pwd);
        $this->handleInvite($user->pid, $user->admin_id, $user->id, $data['rid']);
        return [true, $user];
    }

    /**
     * 邀请处理
     * @param $pid int 邀请人ID
     * @param $admin_id int 代理ID
     * @param $uid int 用户ID
     * @param $rid int 资源ID
     * @param $openid string 用户openid
     */
    protected function handleInvite($pid, $admin_id, $uid, $rid, $openid = '')
    {
        #写入助力记录
        if ($pid) {
            $invite = [
                'admin_id' => $admin_id,
                'rid' => $rid,
                'pid' => $pid,
                'uid' => $uid,
                'ctime' => time()
            ];
            Invite::create($invite, true);
            if (!empty($rid)) {
                #更新资源邀请任务信息
                $task = ResourceTask::where('uid', $pid)->where('rid', $rid)->find();
                if ($task) {
                    $spread = Spread::where('id', $rid)->find();
                    if (!empty($spread)) {
                        $spread = $spread->toArray();
                        $task_over = 0;
                        if ($task->invite_num >= $spread['invite_num'] && ($task->is_video == 1 || $spread['exc_video'] == 0)) {
                            $task_over = 1;
                        }
                        $invite_num = ($task->invite_num + 1);
                        $task->invite_num = ['inc', 1];
                        $task->status = $task_over;
                        $task->utime = time();
                        $task->save();
                        #订阅提醒
                        $template_id = config('setting.subscribe_task_id');
                        $i = UserSubscribe::where(['temp_id' => $template_id, 'rid' => $rid])->where('uid', $pid)->where('result', 'accept')->find();
                        if ($i) {
                            $openid = (new UserThird())->getFieldVal(1, $pid, 'openid');
                            $app = Facade::miniProgram();
                            $thing4 = '任务进度,邀请：' . $invite_num . '/' . $spread['invite_num'];
                            $task_result = '未完成';
                            if ($task_over == 1) {
                                $thing4 = '任务完成,邀请:' . $invite_num . '/' . $spread['invite_num'];
                                $task_result = '任务完成';
                            }
                            if ($spread['exc_video']) {
                                $thing4 .= ',视频：' . $task->is_video . '/1';
                            }
                            $arr = [
                                'template_id' => $template_id,
                                'touser' => $openid,
                                'page' => '/pages/share/jump?fid=' . $admin_id . '&uid=' . $pid . '&type=2&id=' . $rid . '&r_type=' . $spread['type'],
                                'data' => [
                                    'thing3' => ['value' => '任务:' . mb_substr($spread['title'], 0, 11) . '...'],
                                    'thing4' => ['value' => $thing4],
                                    'phrase12' => ['value' => $task_result],
                                    'time14' => ['value' => date('Y.m.d H:i')]
                                ]
                            ];
                            $app->subscribe_message->send($arr);
                        }
                    }
                }
            }
        }
    }

    /**
     * 手机短信验证码登陆，同时兼有手机短信注册的功能，还有第三方账户绑定的功能
     * @param $data
     * @return array
     */
    public function smsLogin($data)
    {
        $userThird = new UserThird();
        //判断是否是登陆
        $userInfo = $this->where(['username' => $data['mobile']])->find();
        if (!$userInfo) {
            //没有此用户，创建此用户
            $new_user['username'] = $data['mobile'];
            $new_user['mobile'] = $data['mobile'];
            $new_user['token'] = md5($data['mobile'] . time());
            //判断是否是微信登陆，如果是，就查出来记录，取他的头像和昵称
            if (isset($data['user_third_id'])) {
                $user_third = $userThird->where(['id' => $data['user_third_id']])->find();
                if ($user_third) {
                    #判断是不是老用户绑定手机号
                    if ($user_third['uid'] > 0) {
                        $userInfo = $this->where(['id' => $user_third->uid])->find();
                        if ($userInfo) {
                            $userInfo->username = $new_user['username'];
                            $userInfo->mobile = $new_user['mobile'];
                            $userInfo->token = $new_user['token'];
                            $userInfo->save();
                            unset($userInfo->pwd);
                            unset($userInfo->salt);
                            return [true, $userInfo];
                        }
                    }
                    if (!isset($data['avatar'])) {
                        $data['avatar'] = $user_third['avatar'];
                    }
                    if (!isset($data['nickname'])) {
                        $data['nickname'] = $user_third['nickname'];
                    }
                    #微信小程序取openid
                    if (empty($data['openid']) && $user_third['type'] == 1) {
                        $data['openid'] = $user_third['openid'];
                    }
                }
            }
            //如果没有头像和昵称，那么就取系统头像和昵称吧
            if (isset($data['avatar'])) {
                $new_user['avatar'] = $data['avatar'];
            } else {
                $new_user['avatar'] = config('setting.web_logo');
            }
            if (isset($data['nickname'])) {
                $new_user['nickname'] = $data['nickname'] . mt_rand(1001, 9999);
            } else {
                $new_user['nickname'] = '用户' . rand_string(6, 0);
            }
            $new_user['salt'] = mt_rand(100001, 999999);
            if (isset($data['pwd'])) {
                $new_user['pwd'] = $this->enPassword($data['pwd'], $new_user['salt']);
            } else {
                $new_user['pwd'] = "";
            }
            $new_user['pid'] = $data['pid'];
            $new_user['admin_id'] = $data['admin_id'];
            $new_user['ctime'] = time();
            $new_user['status'] = 1;
            $userInfo = $this->create($new_user, true);
            if (!$userInfo) {
                return [false, '账号创建失败'];
            }
            $this->handleInvite($userInfo->pid, $userInfo->admin_id, $userInfo->id, $data['rid']);
            unset($userInfo->pwd);
            unset($userInfo->salt);
        } else {
            //如果有这个账号的话，判断一下是不是传密码了，如果传密码了，就是注册，这里就有问题，因为已经注册过
            if (isset($data['pwd'])) {
                return [false, '手机号码已经存在了'];
            }
        }
        //判断是否是微信登陆，如果是，就给他绑定微信账号
        if (isset($data['user_third_id'])) {
            $userThird->save(['uid' => $userInfo['id']], ['id' => $data['user_third_id']]);
        }
        return [true, $userInfo];
    }

    /**
     * 计算两级分销收益
     * @param $type int 类型 1资源，2 SVIP
     * @param $money float 金额
     * @param $pid int 邀请人ID
     * @param $spread mixed 资源
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function computeCommission($type, $money, $pid, $spread)
    {
        $hr_money = 0;
        $sr_money = 0;
        if ($money > 0 && $pid > 0) {
            #判断会员是否具有分销权限
            $user = $this->where(['id' => $pid, 'status' => 1])->find();
            if ($user) {
                #一级直接邀请人
                if(time() < $user->exp_time){
                    $rule = Privilege::handleUserAuth($user->vid, '', 5);
                    if ($rule) {
                        if (!is_array($rule)) {
                            $rules = ['sell' => $rule, 'sell2' => 0];
                        } else {
                            $rules = $rule;
                        }
                        if (!empty($rules['sell'])) {
                            switch ($type) {
                                case 1:#资源分销
                                    if ($spread->sell_set == 1) {#按会员设置
                                        $hr_money = bcmul($rules['sell'], $money, 2);
                                    } else {#按资源设置
                                        if ($spread->sell_type == 1) {//是否系统赠送
                                            $hr_money = bcmul($spread->sell, $money, 2);
                                        } else {
                                            $hr_money = $spread->sell;
                                        }
                                    }
                                    break;
                                case 2:#svip分销
                                    $hr_money = bcmul($rules['sell'], $money, 2);
                                    break;
                            }
                        }
                    }
                }
                #二级分销
                if (config('setting.commission_fee') == '2') {
                    if ($user->pid > 0) {
                        $puser = $this->where(['id' => $user->pid, 'status' => 1])->find();
                        if ($puser && (time() < $puser->exp_time)) {
                            $rule2 = Privilege::handleUserAuth($puser->vid, '', 5);
                            if ($rule2) {
                                if (!is_array($rule2)) {
                                    $rules2 = ['sell' => $rule2, 'sell2' => 0];
                                } else {
                                    $rules2 = $rule2;
                                }
                                if (!empty($rules2['sell2'])) {
                                    switch ($type) {
                                        case 1:#资源分销
                                            if ($spread->sell_set == 1) {#按会员设置
                                                $sr_money = bcmul($rules2['sell2'], $money, 2);
                                            } else {#按资源设置
                                                if ($spread->sell_type2 == 1) {//是否百分比
                                                    $sr_money = bcmul($spread->sell2, $money, 2);
                                                } else {
                                                    $sr_money = $spread->sell2;
                                                }
                                            }
                                            break;
                                        case 2:#svip分销
                                            $sr_money = bcmul($rules2['sell2'], $money, 2);
                                            break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return ['hr_money' => $hr_money, 'sr_money' => $sr_money];
    }
}