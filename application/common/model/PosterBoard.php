<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\common\model;

use think\Model;

class PosterBoard extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text'
    ];
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }

}