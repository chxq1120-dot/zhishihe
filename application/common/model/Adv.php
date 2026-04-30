<?php

namespace app\common\model;

use think\Model;

class Adv extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'platform'
    ];
    //定义平台类型
    public $platform = [
        0 => '微信小程序',
        1 => '抖音小程序',
        2 => 'APP',
        3 => '百度小程序'
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //返回平台类型
    public function getPlatformAttr($value, $data)
    {
        return $this->platform[$data['sort']];
    }

    /**
     * 返回广告位设置数据
     * @param int $sort
     * @return array
     */
    public function getAdvData($sort = 0)
    {
        $ban = [];$plug = [];$video = [];$cell = [];$nat = [];
        $data = $this->where(['status' => 1, 'sort' => $sort])->select();
        foreach ($data as $k => $v) {
            switch ($v['type']) {
                case 1:#banner
                    $ban[] = $v;
                    break;
                case 3:#插屏
                    $plug[] = $v;
                    break;
                case 4:#视频
                    $video[] = $v;
                    break;
                case 5:#格子
                    $cell[] = $v;
                    break;
                case 7:#原生
                    $nat[] = $v;
                    break;
            }
        }
        return ['ban' => $ban, 'plug' => $plug, 'video' => $video, 'cell' => $cell, 'nat' => $nat];
    }
}