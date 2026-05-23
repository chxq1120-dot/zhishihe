<?php
namespace app\api\controller;

class Article extends Common
{
    public function initialize()
    {
    }

    public function show()
    {
        $this->success('success', [
            'id' => 1, 'title' => '示例文章', 'content' => '这是一篇示例文章',
            'thumb' => '', 'views' => 0, 'ctime_text' => date('Y-m-d'),
            'sort' => ['name' => '默认分类'], 'desc' => '示例文章内容...'
        ]);
    }

    public function lists()
    {
        $this->success('success', ['list' => [], 'sort_name' => '全部文章']);
    }
}
