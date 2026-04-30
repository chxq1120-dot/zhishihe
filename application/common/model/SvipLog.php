<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;

class SvipLog extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    /**
     * svip关联
     * @return [type] [description]
     */
    public function svip()
    {
        return $this->belongsTo('Svip', 'vid','id')->setEagerlyType(0)->joinType('left');
    }
}