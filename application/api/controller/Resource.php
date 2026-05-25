<?php
namespace app\api\controller;

use app\common\model\SpreadHand;
use app\common\model\Spread;
use app\common\model\ResourceTask;
use app\common\model\AdminSite;
use think\Db;

class Resource extends Common
{
    public function initialize()
    {
    }

    public function resourcesort()
    {
        $list = [
            ['id' => 1, 'pid' => 0, 'name' => '热门资源', 'thumb' => ''],
            ['id' => 2, 'pid' => 0, 'name' => '课程教程', 'thumb' => ''],
            ['id' => 3, 'pid' => 0, 'name' => '工具软件', 'thumb' => ''],
        ];
        $this->success('success', ['list' => $list]);
    }

    public function allsort()
    {
        $list = [
            ['id' => 0, 'name' => '不限分类'],
            ['id' => 1, 'pid' => 0, 'name' => '热门资源', 'children' => []],
            ['id' => 2, 'pid' => 0, 'name' => '课程教程', 'children' => []],
        ];
        $level_list = [];
        $this->success('success', ['list' => $list, 'level_list' => $level_list]);
    }

    public function resource()
    {
        $list = [
            ['id' => 1, 'title' => '芝士盒知识付费系统', 'thumb' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20241007/c514fd92769122adcbc96d94a919f00a.png', 'price' => 99, 'dis_price' => 69, 'sales' => 100, 'is_vip' => 0, 'svip_name' => '会员专享', 'level_name' => '普通', 'desc' => '芝士盒为您提供各类知识付费资源...', 'update' => '2024-10-07'],
            ['id' => 2, 'title' => '副业项目合集', 'thumb' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20241007/c514fd92769122adcbc96d94a919f00a.png', 'price' => 199, 'dis_price' => 129, 'sales' => 88, 'is_vip' => 1, 'svip_name' => 'VIP专享', 'level_name' => 'VIP', 'desc' => '副业项目合集，包含多个热门副业...', 'update' => '2024-10-08'],
        ];
        $this->success('success', ['list' => $list, 'total' => 10]);
    }

    public function resdetail()
    {
        $data = [
            'id' => 1, 'title' => '芝士盒知识付费系统',
            'desc' => '这是一个完整的知识付费系统',
            'price' => 99, 'dis_price' => 69, 'sales' => 100,
            'level' => 0, 'type' => 1,
            'level_name' => '普通', 'svip_name' => '会员专享',
            'is_auth' => 1, 'is_coll' => 0, 'is_subscribe' => 0,
            'video_list' => [], 'audio_list' => [], 'group' => [], 'jump' => []
        ];
        $this->success('success', $data);
    }

    public function subrestask()
    {
        $this->success('success');
    }

    public function download()
    {
        $this->success('success');
    }

    public function handSpread()
    {
        $id = $this->request->param('id/d', 0);
        $r_uid = $this->request->param('r_uid/d', 0);
        $uid = $this->request->uid ?? 0;

        if (empty($id)) {
            $this->error('资源ID不能为空');
        }

        $spread = Spread::where('id', $id)->field('id,invite_num,exc_video')->find();
        if (empty($spread)) {
            $this->error('资源不存在');
        }

        if (empty($r_uid)) {
            $this->error('缺少被助力用户信息');
        }

        $admin_id = $this->request->from_id ?? 1;

        $result_data = [
            'is_self' => false,
            'invite_num' => 0,
            'need_num' => $spread->invite_num,
            'is_complete' => false,
            'msg' => ''
        ];

        if (empty($uid)) {
            $this->success('请先登录后助力', $result_data);
        }

        if ($r_uid == $uid) {
            $result_data['is_self'] = true;
            $result_data['invite_num'] = 0;
            $result_data['is_complete'] = false;
            $result_data['msg'] = '本人访问';
            $this->success('本人访问', $result_data);
        }

        $spreadHand = new SpreadHand();
        $result = $spreadHand->handSpread($r_uid, $uid, $admin_id, $id);

        if ($result[0]) {
            $inviter = ResourceTask::where('uid', $r_uid)->where('rid', $id)->find();
            $result_data['invite_num'] = $inviter ? $inviter->invite_num : 0;
            $result_data['is_complete'] = $inviter ? ($inviter->status == 1) : false;
            $result_data['msg'] = $result[1];
            $this->success($result[1], $result_data);
        } else {
            $result_data['msg'] = $result[1];
            $this->error($result[1], $result_data);
        }
    }

    public function getHandList()
    {
        $id = $this->request->param('id/d', 0);
        $r_uid = $this->request->param('r_uid/d', 0);
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);

        if (empty($id)) {
            $this->error('资源ID不能为空');
        }

        $where = ['rid' => $id];
        if (!empty($r_uid)) {
            $where['r_uid'] = $r_uid;
        }

        $list = SpreadHand::with(['user' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where($where)->order('ctime desc')->page($page, $limit)->select();

        $total = SpreadHand::where($where)->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'uid' => $item->uid,
                'r_uid' => $item->r_uid,
                'rid' => $item->rid,
                'ctime_text' => $item->ctime_text,
                'nickname' => $item->user ? $item->user->nickname : '未知用户',
                'avatar' => $item->user ? $item->user->avatar : '',
            ];
        }

        $spread = Spread::where('id', $id)->field('id,invite_num')->find();
        $inviter_task = null;
        if (!empty($r_uid)) {
            $inviter_task = ResourceTask::where('uid', $r_uid)->where('rid', $id)->find();
        }

        $this->success('success', [
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'invite_num' => $inviter_task ? $inviter_task->invite_num : 0,
            'need_num' => $spread ? $spread->invite_num : 0,
            'is_complete' => $inviter_task ? ($inviter_task->status == 1) : false
        ]);
    }
}
