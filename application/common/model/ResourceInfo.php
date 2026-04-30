<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 资源分类关联表
 */
class ResourceInfo extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = false;
    //关联资源详情
    public function resource()
    {
        return $this->hasOne('Resource', 'id', 'rid')->setEagerlyType(0)->joinType('left');
    }
}