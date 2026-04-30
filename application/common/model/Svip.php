<?php

namespace app\common\model;

use think\Model;

class Svip extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
    ];

    // 获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //获取专享会员名称
    public static function getResSvip($id)
    {
        if($id==0){return $name = '会员专享';}
        $name = self::where('id', $id)->value('name');
        return $name;
    }

    //获取名称
    public static function getSvipName($id, $exp_time)
    {
        $name = self::where('id', $id)->value('name');
        if (empty($name) || $exp_time < time()) {
            $name = '普通用户';
        }
        return $name;
    }

    //获取指定会员折扣价
    public static function getDiscountPrice($vid, $price)
    {
        $dis_price = 0;
        if (empty($vid)) {
            return $dis_price;
        }
        $discount = self::where('id', $vid)->value('discount');
        if (!empty($discount)) {
            $dis_price = bcmul($discount, $price, 2);
        }
        return $dis_price;
    }

    /**
     * 关联代理
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Agent', 'admin_id','id')->setEagerlyType(0)->joinType('left');
    }
}