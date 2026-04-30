<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\common\model;

use think\Model;

class ArticleSort extends Model
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
     * 递归遍历数据输出
     * @param     $arr //要输出的数组
     * @param int $pid //父id
     * @param int $step //节点替换次数
     * @return array
     */
    public function getTree($arr = [], $pid = 0, $step = 0)
    {
        if(!$arr){
            $arr = $this->order('indexid asc')->select();
            if(!$arr->isEmpty()){
                $arr = $arr->toArray();
            }
        }
        $tree = [];
        foreach ($arr as $key => $val) {
            if ($val['pid'] == $pid) {
                $flg              = str_repeat('└─', $step);
                $val['name'] = $flg . $val['name'];
                $tree[]           = $val;
                $tree = array_merge($tree,$this->getTree($arr, $val['id'], $step + 1));
            }
        }
        return $tree;
    }
}