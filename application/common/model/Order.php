<?php
namespace app\common\model;
use think\Model;
class Order extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text',
        'ptime_text'
    ];
    //支付状态
    protected $pay_status=[
        0=>'未支付',
        1=>'已支付',
        2=>'已取消',
    ];
    //支付方式：1微信2支付宝3卡密4抖音
    protected $pay_type=[
        1=>'微信支付',
        2=>'支付宝',
        3=>'卡密兑换',
        4=>'抖音支付'
    ];
    //获取器
    public function getUtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }
    //获取器
    public function getPtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
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
            $query->where('order.ctime','between',[$start,$end]);
        }elseif(!empty($start)&&empty($end)){
            $query->where('order.ctime','>',$start);
        }elseif(empty($start)&&!empty($end)){
            $query->where('order.ctime','<',$end);
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
            $query->where('order.status',$value);
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
            $query->where('order.ordno','like',$value);
        }
    }
    /**
     * 获得订单状态
     * @param $status
     * @return void
     */
    public function getPayStatus($status)
    {
        return $this->pay_status[$status];
    }
    /**
     * 获得支付方式
     * @param $status
     * @return void
     */
    public function getPayType($type)
    {
        return $this->pay_type[$type];
    }
    //所属上级代理
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0)->joinType('left');
    }

    //推广
    public function spread()
    {
        return $this->belongsTo('Spread', 'rid')->setEagerlyType(0)->joinType('left');
    }
    /**
     * 关联用户
     * @return [type] [description]
     */
    public function user(){
        return $this->belongsTo('User', 'uid')->setEagerlyType(0)->joinType('left');
    }
    /**
     * svip关联
     * @return [type] [description]
     */
    public function svip(){
        return $this->belongsTo('Svip', 'vid')->setEagerlyType(0)->joinType('left');
    }

}