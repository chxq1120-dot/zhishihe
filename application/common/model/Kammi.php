<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 卡密
 */
class Kammi extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    public function user()
    {
        return $this->hasOne('User','id','uid')->joinType('left');
    }
    public function spread()
    {
        return $this->hasOne('Spread','id','rid')->joinType('left');
    }
    //会员
    public function svip()
    {
        return $this->hasOne('Svip','id','vid')->joinType('left');
    }

    /**
     * 所属站点
     * @return \think\model\relation\HasOne
     */
    public function agent()
    {
        return $this->hasOne('Admin','id','admin_id')->joinType('left');
    }
}