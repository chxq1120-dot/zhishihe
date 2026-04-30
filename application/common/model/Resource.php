<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
use zp\Tree;

/**
 * 资源表
 */
class Resource extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    /**
     * 资源分类关联表
     * @return [type] [description]
     */
    public function resourcetype(){
    	return $this->hasOne(ResourceType::class,'rid','id');
    }
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
            $query->where('resource.ctime','between',[$start,$end]);
        }elseif(!empty($start)&&empty($end)){
            $query->where('resource.ctime','>',$start);
        }elseif(empty($start)&&!empty($end)){
            $query->where('resource.ctime','<',$end);
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
            $query->where('resource.status',$value);
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
            $query->where('resource.title','like','%'.$value.'%');
        }
    }
    /**
     * 分类查询
     * @param  [type] $query [description]
     * @param  [type] $value [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function searchSortAttr($query, $value, $data)
    {
        if (!empty($value)) {
            $sort_list = ResourceSort::where('status', 1)->order('indexid asc,id asc')->select()->toArray();
            $tree = new Tree();
            $tree->init($sort_list, 'pid');
            $child_ids = $tree->getChildrenIds($value, true);
            $query->join('ResourceType', 'resource.id=ResourceType.rid')->where('ResourceType.sid', 'in', $child_ids);
        }
    }

    //所属上级代理
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0)->joinType('left');
    }
    //所属用户
    public function user()
    {
        return $this->belongsTo('User', 'uid')->setEagerlyType(0)->joinType('left');
    }
    //关联资源详情
    public function info()
    {
        return $this->hasOne('ResourceInfo', 'rid', 'id')->setEagerlyType(0)->joinType('left');
    }
}