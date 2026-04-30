<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\common\model;

use think\Model;

class Article extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text'

    ];
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    /**
     * 分类
     * @return \think\model\relation\BelongsTo
     */
    public function sort()
    {
        return $this->belongsTo('ArticleSort', 'sort_id')->setEagerlyType(0);
    }
    /**
     * 发布人
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0);
    }

    /**
     * 获取文章列表
     * @param int $type 分类ID
     * @param string $order 排序字段
     * @param string $orderType 排序类型
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array|false|mixed|\PDOStatement|string|\think\Collection|Model[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($sort_id = 1, $order = 'id', $orderType = 'desc', $page = 1, $pageSize = 10)
    {
        $list = $this->field('id,thumb,title,views,ctime')
            ->where(['sort_id'=>$sort_id,'status'=>1])
            ->order($order, $orderType)
            ->page($page, $pageSize)
            ->select();
        if($list){
            $list=$list->toArray();
            foreach ($list as $k => $v){
                $list[$k]['ctime'] = date('Y-m-d',strtotime($v['ctime']));
            }
        }
        return $list;
    }

}