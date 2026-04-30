<?php
// +----------------------------------------------------------------------
// | ZHIPALLWCCE [ Wisdom Create Cloud Common ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2021 http://www.zhipall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: workrd <304609001@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\common\model\Article as articleModel;
use app\common\model\ArticleSort;
use think\Db;

class Article extends Common
{
    public function initialize()
    {
        $this->model = new articleModel();
    }
    /**
     * 文章详情
     */
    public function show()
    {
        // 获取并验证ID
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            $this->error('文章不存在');
        }
        // 查找文章并更新视图计数
        $article = $this->model->with(['sort' => ['name']])->where('article.id', $id)->find();
        if (empty($article)) {
            $this->error('文章不存在');
        }
        $this->model->where('id', $id)->setInc('views',1);
        $this->success('success', $article);
    }
    /**
     * 文章列表
     */
    public function lists()
    {
        $sort_id = $this->request->param('sort_id/d', 0);
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $where[] = ['article.status', 'eq', 1];
        $sort_name='全部文章';
        if (!empty($sort_id)) {
            $where[] = ['article.sort_id', 'eq', $sort_id];
            $sort_name=ArticleSort::where('id',$sort_id)->value('name');
        }
        $list = $this->model->with(['sort' => ['name']])->where($where)->order('article.ctime desc')->page($page, $limit)->select();
        foreach ($list as $k=>$v){
            $list[$k]['ctime_text']=date('Y-m-d',strtotime($v['ctime_text']));
            $list[$k]['desc']=str_cut(strip_tags($v['content']), 50, '') . '...';
        }
        $this->success('success', ['list' => $list,'sort_name'=>$sort_name]);
    }
}