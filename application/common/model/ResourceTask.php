<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 资源表
 */
class ResourceTask extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    /**
     * 时间范围查询
     * @param  [type] $query [description]
     * @param  [type] $value [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function searchCtimeAttr($query,$value, $data)
    {
        $start=$value[0]?strtotime($value[0]):0;
        $end=$value[1]?strtotime($value[1]):0;
        if(!empty($start)&&!empty($end)){
            $query->where('resource_task.ctime','between',[$start,$end]);
        }elseif(!empty($start)&&empty($end)){
            $query->where('resource_task.ctime','>',$start);
        }elseif(empty($start)&&!empty($end)){
            $query->where('resource_task.ctime','<',$end);
        }
    }
    /**
     * 状态查询
     * @param  [type] $query [description]
     * @param  [type] $value [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function searchStatusAttr($query,$value, $data)
    {
        if(strlen($value)){
            $query->where('resource_task.status',$value);
        }       
    }
    /**
     * 关键字查询
     * @param  [type] $query [description]
     * @param  [type] $value [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function searchNameAttr($query,$value, $data)
    {
        if(!empty($value)){
            $query->where('user.nickname','like','%'.$value.'%');
        }
    }
    /**
     * 资源表
     * @return [type] [description]
     */
    public function spread(){
    	return $this->hasOne(Spread::class,'id','rid');
    }
    /**
     * 关联用户
     * @return [type] [description]
     */
    public function user(){
        return $this->hasOne(User::class,'id','uid');
    }
}