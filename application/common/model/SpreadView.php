<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 资源分类关联表
 */
class SpreadView extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
}