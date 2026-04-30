<?php

namespace app\common\model;
use think\Model;
class AdvSet extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'platform'
    ];
    public $platform=[
        0 => '微信小程序',
        1 => '抖音小程序',
        2 => 'APP',
        3 => '百度小程序'
    ];
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //返回平台类型
    public function getPlatformAttr($value, $data)
    {
        return $this->platform[$data['sort']];
    }
}