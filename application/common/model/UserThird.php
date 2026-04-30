<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\model;

use think\Model;

class UserThird extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text'
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //获取器
    public function getUTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }

    /**
     * 获取指定字段的值
     */
    public function getFieldVal($type, $uid, $name)
    {
        return $this->where(['uid' => $uid, 'type' => $type])->value($name);
    }
    /**
     * 保存第三方授权信息
     */
    public function saveThird($data, $type)
    {
        if (!empty($data['unionid'])) {
            $where['unionid'] = $data['unionid'];
        } else {
            $where['openid'] = $data['openid'];
        }
        $data['type'] = $type;
        $third_id = $this->where($where)->value('id');
        if (!empty($third_id)) {
            $this->allowField(true)->save($data, ['id' => $third_id]);
            $user_third_id = $third_id;
        } else {
            #兼容老用户开启开放平台绑定=3.0版本登录
            $third_id = $this->where('openid',$data['openid'])->value('id');
            if (!empty($third_id)) {
                $this->allowField(true)->save($data, ['id' => $third_id]);
                $user_third_id = $third_id;
            }else{
                #兼容2.0版本授权登录
                $data['uid'] = $this->getUserId($data, $type);
                #创建第三方登录信息
                $user_third = $this->create($data, true);
                $user_third_id = $user_third->id;
            }
        }
        return $user_third_id;
    }

    /**
     * 兼容2.0版本登录
     * @param $data
     * @param $type
     * @return integer
     */
    protected function getUserId($data, $type)
    {
        switch ($type) {
            case 1:#小程序
                $where['openid'] = $data['openid'];
                break;
            case 2:#公众号
                $where['acc_openid'] = $data['openid'];
                break;
            case 3:#小程序
                $where['tt_openid'] = $data['openid'];
                break;
            default:#其他
                $where['openid'] = $data['openid'];
                break;
        }
        $user_id = User::where($where)->value('id');
        return empty($user_id) ? 0 : $user_id;
    }

    /*
     * 处理第三方授权登录数据
     */
    public function handleThird($data, $type)
    {
        $info = $this->where(['id' => $data['user_third_id']])->find();
        if (!$info) {
            return [false, '登录失败'];
        }
        //加密信息里有openid或unionid，前台传过来的值查出来的数据里也有，需要判断是否一致，否则可能会有漏洞
        if ($info->openid != $data['openid'] && $info->unionid != $data['unionid']) {
            return [false, '登录失败，openid不一致'];
        }
        //查询是否有unionid登录
        if (!empty($data['unionid'])) {
            $where[] = ['unionid', 'eq', $data['unionid']];
            $where[] = ['uid', 'neq', '0'];
            $uid = $this->where($where)->value('uid');
            $data['uid'] = !empty($uid) ? $uid : 0;
        }
        $data['type'] = $type;
        //更新第三方登录用户信息
        $info->allowField(true)->save($data);
        //组合数据
        $user['openid'] = empty($data['openid']) ? '' : $data['openid'];
        $user['rid'] = empty($data['rid']) ? 0 : $data['rid'];
        $user['pid'] = $data['invite_uid'];
        $user['admin_id'] = $data['from_id'];
        //如果是新用户，并且不需要绑定手机号码的话，就创建用户
        if ($info->uid == 0 && config('setting.is_bind_mobile') == '1') {
            $user['nickname'] = ($data['nickname'] == '微信用户') ? $data['nickname'] . rand_string(6,0) : $data['nickname'];
            $user['avatar'] = $data['avatar'];
            $user['sex'] = empty($data['sex']) ? '0' : $data['sex'];
            list($res, $newUser) = (new User())->createUser($user);
            if (!$res) {
                return [false, $newUser];
            }
            $info->uid = $newUser->id;
            $info->utime = time();
            $info->save();
        }
        //到这里，如果没有用户id，就需要去绑定手机号码了。
        if ($info->uid == 0) {
            //未绑定用户，需要先绑定手机号码
            return [true, ['user_third_id' => $info->id]];
        } else {
            $userModel = new User();
            $user = $userModel->where(['id'=>$info->uid,'status'=>1])->find();
            if (!$user) {
                return [false, '用户不存在或账号被禁用'];
            }
            #兼容原有旧账号绑定手机号码
            if(empty($user->username) && config('setting.is_bind_mobile') == '2'){
                #需要绑定手机号码
                return [true, ['user_third_id' => $info->id]];
            }
            $user->token = md5($info->uid . time());
            $user->login_time = time();
            $user->save();
            unset($user->pwd);
            unset($user->salt);
            return [true, $user];
        }
    }
}