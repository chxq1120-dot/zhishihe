<?php
namespace app\common\model;
use think\Model;
class UserResource extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    /**
     * 关联用户
     * @return [type] [description]
     */
    public function user(){
        return $this->hasOne(User::class,'id','uid');
    }
    /**
     * 资源表
     * @return [type] [description]
     */
    public function spread(){
    	return $this->hasOne(Spread::class,'id','rid');
    }
}