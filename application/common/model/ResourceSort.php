<?php
declare (strict_types = 1);
namespace app\common\model;

use think\Model;
/**
 * 资源分类表
 */
class ResourceSort extends Model{
	// 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    /**
     * 根据父级ID获取全部子类信息
     * @param int $pid
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildByPid($pid = 0)
    {
        $where = ['pid'=>$pid,'status'=>1];
        $sorts = $this->field('id,pid,name,thumb,indexid')
            ->where($where)
            ->order('indexid asc')
            ->select();
        return $sorts;
    }

    /**
     * 获取分类
     * @param int $pid
     * @param int $limit
     * @return array|\PDOStatement|string|\think\Collection|Model[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildSort($pid = 0,$limit=0)
    {
        $where = ['pid'=>$pid,'status'=>1];
        $sort = $this->field('id, name,thumb,indexid')->where($where)->order('indexid asc');
        if(!empty($limit)){
            $data=$sort->limit($limit)->select();
        }else{
            $data =$sort->select();
        }
        return $data;
    }

    /**
     * 获取全部分类
     * @param bool $id //排除分类ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllSort($id = false,$show = false)
    {
        $where = [];
        if($id){
            $where[] = ['id', 'neq', $id];
            $where[] = ['pid', 'neq', $id];
        }
        if(!$show){
            $where[] = array('status', 'eq', 1);
        }
        $data = $this->field('id, pid, name, indexid, thumb')
            ->where($where)
            ->order('indexid asc')
            ->select();
        $result = $this->getTreeApi($data);
        return $result;
    }


    /**
     * API使用的树装
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getTreeApi($data)
    {
        $new_data = array();
        foreach($data as $v)
        {
            if($v['pid'] == 0)
            {
                $new_data[$v['id']]['id'] = $v['id'];
                $new_data[$v['id']]['name'] = $v['name'];
                $new_data[$v['id']]['thumb'] = $v['thumb'];
                $new_data[$v['id']]['indexid'] = $v['indexid'];
                $new_data[$v['id']]['child'] = [];
            }
        }
        foreach($data as $v)
        {
            if($v['pid'] !== 0)
            {
                if(isset($new_data[$v['pid']]))
                {
                    $new_data[$v['pid']]['child'][] = array(
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'thumb' => $v['thumb'],
                        'indexid' => $v['indexid']
                    );
                }
            }
        }
        $edition = [];
        foreach ((array)$new_data as $key => $val)
        {
            $edition[] = $val['indexid'];
        }
        array_multisort($edition, SORT_ASC, $new_data);
        return $new_data;
    }
}