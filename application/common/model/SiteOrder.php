<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\model;

use think\Exception;
use think\Model;

class SiteOrder extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $updateTime = 'utime';

    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text'
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //获取器
    public function getUtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    /**
     * 用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'uid')->setEagerlyType(0);
    }

    //所属上级代理
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0)->joinType('left');
    }
    /**
     * svip关联
     * @return [type] [description]
     */
    public function svip(){
        return $this->belongsTo('Svip', 'svip_id')->setEagerlyType(0)->joinType('left');
    }
    /**
     * 创建分站数据
     * @param $data
     * @param $type
     * @return array
     */
    public static function createSiteOrder($data, $type = 0)
    {
        try {
            if (empty($data['ordno'])) {
                return [false, '操作失败，请稍后再试'];
            }
            if (empty($data['money'])) {
                return [false, '操作失败，请稍后再试'];
            }
            $order = SiteOrder::where('ordno', $data['ordno'])->find();
            if (empty($order)) {
                unset($data['id']);
                $order = SiteOrder::create($data, true);
            }
            if (empty($order)) {
                return [false, '操作失败，请稍后再试'];
            }
            #卡密支付直接开通分站
            if ($type == 1) {
                $sites = [
                    'username' => $order['username'],
                    'password' => $order['password'],
                    'prefix' => $order['prefix'],
                    'suffix' => $order['suffix'],
                    'mobile' => $order['mobile'],
                    'svip_id' => $order['svip_id'],
                    'webname' => $order['webname'],
                    'admin_id' => $order['admin_id'],
                ];
                list($res, $info) = (new Agent())->createSite($sites);
                if (!$res) {
                    return [false, $info];
                }
                $res = (new User())->save(['site_uid' => $info['agent_id']], ['id' => $order->uid]);
                if (!$res) {
                    return [false, '代理分站创建失败'];
                }
                #更新站点订单
                $order->status = 1;
                $order->utime = time();
                $order->save();
            }
            return [true, 'success'];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}