<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 资源等级关联表
 */
class ResourceLevel extends Model
{
    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    //获取等级名称
    public function getLevelName($id)
    {
        return $this->where('id', $id)->value('name');
    }
}