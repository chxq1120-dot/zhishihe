<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
use zp\Tree;

/**
 * 资源表
 */
class Spread extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    //所属SVIP
    public function svip()
    {
        return $this->belongsTo('Svip', 'is_vip')->setEagerlyType(0)->joinType('left');
    }
    //所属资源等级
    public function level()
    {
        return $this->belongsTo('ResourceLevel', 'level')->setEagerlyType(0)->joinType('left');
    }
    //关联资源详情
    public function info()
    {
        return $this->hasOne('ResourceInfo', 'rid', 'rid')->setEagerlyType(0);
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
            $query->where('spread.ctime','between',[$start,$end]);
        }elseif(!empty($start)&&empty($end)){
            $query->where('spread.ctime','>',$start);
        }elseif(empty($start)&&!empty($end)){
            $query->where('spread.ctime','<',$end);
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
            $query->where('spread.status',$value);
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
            $query->where('spread.title','like','%'.$value.'%');
        }
    }
    /**
     * 分类查询
     * @param  [type] $query [description]
     * @param  [type] $value [description]
     * @param  [type] $data  [description]
     * @return [type]        [description]
     */
    public function searchSortAttr($query,$value, $data)
    {
        if(!empty($value)){
            $sort_list = ResourceSort::where('status', 1)->order('indexid asc,id asc')->select()->toArray();
            $tree = new Tree();
            $tree->init($sort_list, 'pid');
            $child_ids = $tree->getChildrenIds($value, true);
            $query->join('SpreadType','spread.id=SpreadType.rid')->where('SpreadType.sid','in',$child_ids);
        }
    }

    /**
     * 获取推广资源列表
     * @param string $field
     * @param array $where
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($field='*',$where=[],$order = 'id desc', $page = 1, $limit = 10){
        $result=[
            'status'=>200,
            'msg'=>'',
            'data'=>[]
        ];
        $spread=$this->where($where)->field($field)->order($order)->page($page, $limit)->select();
        if(!$spread->isEmpty()){
            foreach($spread as $k=>$v){
                $spread[$k]['level_name']=(new ResourceLevel())->getLevelName($v['level']);
                $spread[$k]['update'] = formatTime(strtotime($v['ctime']));
                if (!empty($v['utime'])) {
                    $spread[$k]['update'] = formatTime($v['utime']);
                }
            }
            $result['data']=$spread->toArray();
        }
        return $result;
    }

    /**
     * 获取推广资源详情
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDetials($id=0,$field='*'){
        $result=[
            'status'=>200,
            'msg'=>'',
            'data'=>[]
        ];
        $spread=$this->where('id',$id)->field($field)->find();
        if(!empty($spread)){
            $spread['level_name']=(new ResourceLevel())->getLevelName($spread['level']);
            $result['data']=$spread->toArray();
        }
        return $result;
    }

}