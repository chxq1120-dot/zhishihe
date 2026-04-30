<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 资源表
 */
class WechatKey extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text'
    ];
    // 获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
    }
    public function getUtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
    }
}    