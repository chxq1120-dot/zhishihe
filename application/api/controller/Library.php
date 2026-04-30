<?php
// +----------------------------------------------------------------------
// | ZHIPALLWCCE [ Wisdom Create Cloud Common ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2021 http://www.zhipall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: workrd <304609001@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\common\model\Admin;
use app\common\model\AudioCourse;
use app\common\model\ResourceSort;
use app\common\model\VideoCourse;
use GuzzleHttp\Client;
use app\common\model\ResourceType;
use think\Db;

class Library extends Common
{
    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\Resource();
        $this->middleware = [
            'cross',
            'Auth' => [
                'except' => [
                    'getlist',
                    'getInfo',
                    'getSort',
                    'getSelect',
                    'getFind',
                    'getCourse'
                ]
            ]
        ];
    }

    /**
     * 权限检测
     * @return array
     */
    protected function checkToken()
    {
        $token=$this->request->header('token');
        $from_id=$this->request->param('from_id');
        $platform=Admin::where('id',$from_id)->find();
        if(!$platform){
            $this->error('同步平台不存在');
        }
        if($platform->master_token!==$token){
            $this->error('主资源库接口授权码错误');
        }
    }
    /**
     * 获取主库资源列表
     * @return [type] [description]
     */
    public function getlist()
    {
        $this->checkToken();
        $data = input('param.');
        $list = $this->model->where('a.status', '=', 1)->where('a.type', '<', 6);
        //关键词搜索
        if (!empty($data['search'])) {
            $list = $list->where('a.title', 'like', '%' . $data['search'] . '%');
        }
        //分类搜索
        if (!empty($data['sid']) && empty($data['cid'])) {
            $allcid = ResourceSort::where('pid', $data['sid'])->column('id');
            $allcid[] = $data['sid'];
            $list = $list->join('resource_type c', 'a.id=c.rid')
                ->where('c.sid', 'in', $allcid);
        }
        if (!empty($data['cid'])) {
            $list = $list->join('resource_type c', 'a.id=c.rid')
                ->where('c.sid', '=', $data['cid']);
        }
        //类型搜索
        if (!empty($data['type'])) {
            $list = $list->where('a.type', '=', $data['type']);
        }
        //价格搜索
        if (!empty($data['price'])) {
            if ($data['price'] == 1) {
                $list = $list->where('a.money', '=', 0.00);
            } elseif ($data['price'] == 2) {
                $list = $list->where('a.money', 'between', [0, 10]);
            } elseif ($data['price'] == 3) {
                $list = $list->where('a.money', 'between', [10, 20]);
            } elseif ($data['price'] == 4) {
                $list = $list->where('a.money', 'between', [20, 30]);
            } elseif ($data['price'] == 5) {
                $list = $list->where('a.money', 'between', [30, 50]);
            } elseif ($data['price'] == 6) {
                $list = $list->where('a.money', 'between', [50, 100]);
            } elseif ($data['price'] == 7) {
                $list = $list->where('a.money', 'between', [100, 200]);
            } elseif ($data['price'] == 8) {
                $list = $list->where('a.money', '>', 200);
            }
        }
        $field = 'a.*';
        $total = $list->field($field)->alias('a')->count();
        $lists = $list->field($field)->alias('a')->page($data['page'], $data['limit'])->order('a.id desc')->select();
        foreach ($lists as $k => $v) {
            $sorts = ResourceType::field('b.name')->alias('a')->where('rid', $v['id'])->join('resource_sort b', 'a.sid=b.id')->column('b.name');
            $lists[$k]['sorts'] = implode(',', $sorts);
        }
        $arr = ['code' => 200, 'data' => $lists, 'total' => $total];
        return json($arr);
    }

    /**
     * 获取资源详情
     * @return [type] [description]
     */
    public function getInfo()
    {
        $this->checkToken();
        $data = input('param.');
        $info = $this->model->alias('a')->join('resource_info b','a.id=b.rid')->where('a.id', $data['id'])->field('a.*,b.free_content,b.content')->find();
        if (!$info) {
            $this->error('获取失败');
        }
        $sort = ResourceType::field('b.name')
            ->alias('a')->where('rid', $data['id'])
            ->join('resource_sort b', 'a.sid=b.id')->select();
        $info['sort'] = $sort;
        $this->success('success', $info);
    }

    /**
     * 获取资源分类
     */
    public function getSort()
    {
        $this->checkToken();
        $sid = $this->request->request('sid/d', 0);
        $where[] = ['status', 'eq', 1];
        if (!empty($sid)) {
            $where[] = ['pid', 'eq', $sid];
        }
        $list = ResourceSort::where($where)->order('indexid asc,id asc')->select();
        $this->success('success', $list);
    }

    /**
     * 获取指定分类
     */
    public function getFind()
    {
        $this->checkToken();
        $pid = $this->request->request('pid/d', 0);
        $where[] = ['status', 'eq', 1];
        if (!empty($pid)) {
            $where[] = ['id', 'eq', $pid];
        }
        $list = ResourceSort::where($where)->find();
        $this->success('success', $list);
    }

    /**
     * 获取课程章节
     */
    public function getCourse()
    {
        $this->checkToken();
        $id = $this->request->request('id/d', 0);
        $type = $this->request->request('type/d', 0);
        if ($type == 4) {
            $courseModel = new VideoCourse();
        } else {
            $courseModel = new AudioCourse();
        }
        $list = $courseModel->where('rid',$id)->order('id asc')->select();
        $this->success('success', $list);
    }

    /**
     * 获取同步资源内容
     * @return [type] [description]
     */
    public function getSelect()
    {
        $this->checkToken();
        $data = input('param.');
        $where[] = ['a.status', '=', 1];
        if (!empty($data['ids'])) {
            $where[] = ['a.id', 'in', $data['ids']];
        }
        $resources = $this->model->alias('a')->join('resource_info b','a.id=b.rid')->where($where)->field('a.*,b.free_content,b.content')->order('a.id asc')->select();
        if (!$resources) {
            $this->error('获取失败');
        }
        foreach ($resources as &$resource) {
            $sort = ResourceType::field('b.id,b.pid,b.name,b.thumb')
                ->alias('a')->where('rid', $resource['id'])
                ->join('resource_sort b', 'a.sid=b.id')->select();
            $resource['sort'] = $sort;
        }
        $this->success('success', $resources);
    }
}