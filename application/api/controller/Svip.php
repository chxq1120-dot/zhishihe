<?php

namespace app\api\controller;

use app\common\model\Admin;
use Naixiaoxin\ThinkWechat\Facade;
use app\common\model\{
    User,
    Svip as SvipModel,
    Privilege,
    SvipPrivilege
};
use app\api\validate\Check;

class Svip extends Common
{
    /**
     * 获取会员类型
     * @return [type] [description]
     */
    public function sviptype()
    {
        if (request()->isPost()) {
            $admin_id = $this->request->param('from_id/d', 0);
            $default_admin_id = (new Admin())->getDefaultAdminId();
            $where = ['admin_id' => $admin_id];
            if ($admin_id != $default_admin_id) {
                $svip_num = SvipModel::where(['admin_id' => $admin_id, 'status' => 1])->field('id')->find();
                if (empty($svip_num)) {
                    $where = ['admin_id' => $default_admin_id];
                }
            }

            $type = $this->request->param('type/d', 0);
            $field = 'id,name,days,price,content,invite_num,discount';
            $list = SvipModel::field($field)->where(['type' => $type, 'status' => 1])->where($where)->order('level asc')->select();
            foreach ($list as $k => $v) {
                $list[$k]['price'] = floatval($v['price']);
                $list[$k]['days'] = $v['days'] . '天';
                if ($v['days'] >= 9999) {
                    $list[$k]['days'] = '永久';
                }
                $rulelist = SvipPrivilege::alias('a')->field('b.type,b.name,b.rule,b.url,b.desc')->where('vid', $v['id'])->join('Privilege b', 'a.pid=b.id')->select();
                foreach ($rulelist as $k1 => $v1) {
                    $rules = unserialize($v1['rule']);
                    switch ($v1['type']) {
                        case 1:#等级
                            $name_arr = array_column($rules, 'name');
                            $remark = implode(',', $name_arr);
                            break;
                        case 2:#数量
                            $remark = $rules . '个';
                            break;
                        case 3:#分类
                            $name_arr = array_column($rules, 'name');
                            $remark = implode(',', $name_arr);
                            break;
                        case 4:#是否开启广告
                            if ($rules['status'] == 1) {
                                $remark = '有广告';
                            } else {
                                $remark = '无广告';
                            }
                            break;
                        case 5:#分销
                            if (!is_array($rules)) {
                                $remark = bcmul($rules, 100, 0) . '%';
                            } else {
                                $remark = '一级:' . bcmul($rules['sell'], 100, 0) . '%,二级:' . bcmul($rules['sell2'], 100, 0) . '%';
                            }
                            break;
                    }
                    $rulelist[$k1]['rule'] = $remark;
                }
                $list[$k]['rules'] = $rulelist;
            }
            $this->success('success', ['list' => $list]);
        }
    }

    /**
     * 获取会员权限信息
     * @return [type] [description]
     */
    public function svipinfo()
    {
        if (request()->isPost()) {
            $type = $this->request->param('type/d', 0);
            $admin_id = $this->request->param('from_id/d', 0);
            $default_admin_id = (new Admin())->getDefaultAdminId();
            $where = ['admin_id' => $admin_id];
            if ($admin_id != $default_admin_id) {
                $svip_num = SvipModel::where(['admin_id' => $admin_id, 'status' => 1])->field('id')->find();
                if (empty($svip_num)) {
                    $where = ['admin_id' => $default_admin_id];
                }
            }
            $sviplist = SvipModel::field('id,name,days,price,invite_num')->where($where)->where(['type' => $type, 'status' => 1])->select();
            foreach ($sviplist as $k => $v) {
                $rulelist = SvipPrivilege::alias('a')->field('b.type,b.name,b.rule')->where('vid', $v['id'])->join('Privilege b', 'a.pid=b.id')->select();
                foreach ($rulelist as $k1 => $v1) {
                    $rules = unserialize($v1['rule']);
                    switch ($v1['type']) {
                        case 1:#等级
                            $name_arr = array_column($rules, 'name');
                            $remark = implode(',', $name_arr);
                            break;
                        case 2:#数量
                            $remark = $rules . '个';
                            break;
                        case 3:#分类
                            $name_arr = array_column($rules, 'name');
                            $remark = implode(',', $name_arr);
                            break;
                        case 4:#是否开启广告
                            if ($rules['status'] == 1) {
                                $remark = '有广告';
                            } else {
                                $remark = '无广告';
                            }
                            break;
                        case 5:#分销
                            if (!is_array($rules)) {
                                $remark = bcmul($rules, 100, 0) . '%';
                            } else {
                                $remark = '一级:' . bcmul($rules['sell'], 100, 0) . '%,二级:' . bcmul($rules['sell2'], 100, 0) . '%';
                            }
                            break;
                    }
                    $rulelist[$k1]['rule'] = $remark;
                }
                $sviplist[$k]['rules'] = $rulelist;
                $sviplist[$k]['days'] = $v['days'] . '天';
                if ($v['days'] >= 9999) {
                    $sviplist[$k]['days'] = '永久';
                }
            }
            $this->success('success', $sviplist);
        }
    }
}