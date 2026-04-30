<?php
namespace app\common\model;
use think\Model;
class UserCash extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;
    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text'
    ];
    // 获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
    }
    public function getUtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
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
            $query->where('user_cash.ctime','between',[$start,$end]);
        }elseif(!empty($start)&&empty($end)){
            $query->where('user_cash.ctime','>',$start);
        }elseif(empty($start)&&!empty($end)){
            $query->where('user_cash.ctime','<',$end);
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
            $query->where('user_cash.status',$value);
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
     * 关联用户
     * @return [type] [description]
     */
    public function user(){
        return $this->hasOne(User::class,'id','uid');
    }

    /**
     * 关联代理商
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id','id')->setEagerlyType(0)->joinType('left');
    }
}