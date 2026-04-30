<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 音频课程
 */
class AudioCourse extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    public function views()
    {
        return $this->belongsTo('ViewLog', 'id','cid')->setEagerlyType(0)->joinType('left');
    }

}