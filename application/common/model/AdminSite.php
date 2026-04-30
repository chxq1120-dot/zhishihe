<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\model;

use think\Model;

class AdminSite extends Model
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
     * 代理用户
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0);
    }

    /**
     * 获取代理地址
     * @param $admin_id
     */
    public function getSiteUrl($admin_id)
    {
        #默认h5地址
        $url = config('setting.account_domain');
        $sites = $this->where('admin_id', $admin_id)->find();
        if (!empty($sites)) {
            if ($sites->sub_type == 0) {
                $url = 'http://' . $sites->sub_prefix . '.' . $sites->base_domain;
            } else {
                $url = 'http://' . $sites->domain;
            }
            if ($sites->is_ssl == 1) {
                $url = str_replace('http://', 'https://', $url);
            }
        }
        return $url;
    }

    /**
     * 根据域名查询代理ID
     */
    public function getSubAdminId($domain)
    {
        $admin_id = (new Admin())->getDefaultAdminId();
        if (!empty($domain)) {
            $sub_prefix = $domain;
            if (stripos($domain, '.') !== false) {
                $sub_prefix = explode('.', $domain)[0];
            }
            $sites = $this->where('sub_prefix', $sub_prefix)->whereOr('domain', $domain)->find();
            if (!empty($sites)) {
                $admin_id = $sites->admin_id;
            }
        }
        return $admin_id;
    }
}