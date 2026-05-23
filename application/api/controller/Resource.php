<?php
namespace app\api\controller;

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
}
