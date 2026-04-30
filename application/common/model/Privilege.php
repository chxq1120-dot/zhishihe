<?php

namespace app\common\model;

use think\Model;

class Privilege extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    // protected $append = [
    //     'ctime_text',
    // ];
    //获取器
    // public function getCtimeTextAttr($value,$data){
    //     $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    /*
     * 会员特权
     * @param int 会员级别特权vID
     * @param mixed 特权鉴定值
     * @param int 特权类型
     * @return mixed
     */
    public static function handleUserAuth($vid, $rule,$type)
    {
        $auth = self::alias('a')
            ->join('svip_privilege b','a.id=b.pid')
            ->where('a.type',$type)
            ->where('b.vid', $vid)->find();
        if ($auth) {
            $rules = unserialize($auth->rule);
            switch ($type) {
                case 1:#资源级别
                    if (in_array($rule, array_column($rules, 'level'))) {
                        return 2;
                    }
                    return 1;
                    break;
                case 2:#资源数量
                    if (intval($rule) < intval($rules)) {
                        return 2;
                    }
                    return 1;
                    break;
                case 3:#资源分类
                    $rules_id=array_column($rules, 'id');
                    $rules_childs=getParentChild($rules_id);
                    if (array_intersect($rule, $rules_childs) || in_array(0, array_column($rules, 'id'))) {
                        return 2;
                    }
                    return 1;
                    break;
                case 4:#站内广告
                    if($rules['status']==0){
                        return true;
                    }
                    break;
                case 5:#分销佣金
                    return $rules;
                    break;
                case 6:#注册奖励
                    return $rules;
                    break;
            }
            return false;
        }
        return false;
    }
}