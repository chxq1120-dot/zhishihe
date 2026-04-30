<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use zp\Tree;

class Widget extends Common
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     *  加载资源列表模板
     * @return mixed
     */
    public function getResourceList()
    {
        $this->view->engine->layout(false);
        return $this->fetch('getResource');
    }

    /**
     *  加载资源分类模板
     * @return mixed
     */
    public function getResourceSort()
    {
        $this->view->engine->layout(false);
        return $this->fetch('getResourceSort');
    }

    /**
     * 加载文章模板
     * @return mixed
     */
    public function getArticle()
    {
        $this->view->engine->layout(false);
        $article_id_key = $this->request->param('article_id_key');
        $article_name_key = $this->request->param('article_name_key');
        $this->assign('article_id_key', $article_id_key);
        $this->assign('article_name_key', $article_name_key);
        return $this->fetch('getArticle');
    }

    /**
     * 文章分类模板
     * @return mixed
     */
    public function getArticleSort()
    {
        $this->view->engine->layout(false);
        return $this->fetch('getArticleSort');
    }

    /**
     * 获取前端页面模板
     * @return mixed
     */
    public function getPages()
    {
        $this->view->engine->layout(false);
        return $this->fetch('getPages');
    }

    /**
     * 获取文章内容
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function articleInfo()
    {
        $ids = $this->request->param('id/d', 0);
        $articleModel = new \app\common\model\Article();
        return $articleModel->field('id,title')->where('id', $ids)->find();
    }

    /**
     * 获取资源内容
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function spreadInfo()
    {
        $ids = $this->request->param('id/d', 0);
        $spreadModel = new \app\common\model\Spread();
        return $spreadModel->field('id,title,price')->where('id', $ids)->find();
    }

    /**
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function articleSortInfo()
    {
        $ids = $this->request->param('id/d', 0);
        $articleSort = new \app\common\model\ArticleSort();
        return $articleSort->field('id,name')->where('id', $ids)->find();
    }

    /**
     * 页面列表
     */
    public function pageList()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\common\model\Pages();
            $this->relationSearch = false;
            $this->searchFields = 'name';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    //普通页面
    public function tagPageChild()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            $id=$this->request->param('id/d');
            $this->model = new \app\common\model\Pages();
            $list = $this->model->where('pid',$id)->order('id asc')->select();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list];
            return json($result);
        }
    }
    /**
     * 页面列表数据
     */
    public function tagPages()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            $this->model = new \app\common\model\Pages();
            $sort_list=[];
            $list = $this->model->where('type','<','3')->order('id asc')->select();
            if(!$list->isEmpty()){
                $list = $list->toArray();
                $tree=new Tree();
                $tree->init($list, 'pid', '', 'children');
                $sort_list = $tree->getTreeArray('pid');
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $sort_list];
            return json($result);
        }
    }
    /**
     * 获取文章列表
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tagNotice()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\common\model\Article();
            $this->relationSearch = false;
            $this->searchFields = 'title';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->field('id,thumb,title,views,ctime')
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->field('id,thumb,title,views,ctime')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['ctime'] = date('Y-m-d', strtotime($v['ctime']));
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 获取文章分类
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tagNoticeSort()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\common\model\ArticleSort();
            $this->relationSearch = false;
            $this->searchFields = 'name';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['ctime'] = date('Y-m-d', strtotime($v['ctime']));
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 获取资源列表
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tagSpread()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\common\model\Spread();
            $this->relationSearch = false;
            $this->searchFields = 'title';
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->field('id,title,type,thumb,price,dis_price,is_top,level,utime,ctime')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->field('id,title,type,thumb,price,dis_price,is_top,level,utime,ctime')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $list[$key]['level_name'] = getLevelLabel($item['level']);
                $list[$key]['sort_list'] = getSortAttr($item['id']);
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取资源分类
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tagSorts()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->model = new \app\common\model\ResourceSort();
            $sort_list=[];
            $total=0;
            $list = $this->model->where('status',1)->order('id asc')->select();
            if(!$list->isEmpty()){
                $list = $list->toArray();
                foreach ($list as &$item){
                    $item['url'] = '/pages/resource/index?id='.$item['id'];
                }
                $total=count($list);
                $tree=new Tree();
                $tree->init($list, 'pid', '', 'children');
                $sort_list = $tree->getTreeArray('pid');
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $sort_list,'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }

}
