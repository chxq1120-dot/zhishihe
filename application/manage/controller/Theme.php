<?php

/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\PosterConfig;
use app\common\model\ResourceSort;
use app\common\model\Theme as themeModel;
use app\common\model\ThemeConfig;
use app\common\model\ThemeItems;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use zp\Tree;

class Theme extends Common
{
    protected $sceneTag = 'Theme';
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'admin_id';

    public function initialize()
    {
        parent::initialize();
        $this->model = new themeModel();
    }
    /**
     * 移动端首页布局
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('type', 2)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type', 2)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * PC端首页布局
     */
    public function saveTheme()
    {
        if ($this->request->isAjax()) {
            $data = input('post.data/s');
            $code = input('post.pageCode/s', 'desktop_home');
            $theme = $this->model->where('code', $code)->find();
            if (empty($theme)) {
                $page_config=[
                    'name'=>'电脑端首页',
                    'code'=>'desktop_home',
                ];
            }else{
                $page_config=[
                    'name'=>$theme->name,
                    'code'=>$theme->code,
                ];
            }
            $itemsModel = new ThemeItems();
            if(empty($data)){
                return callback(400, '请设置布局');
            }
            $data=json_decode($data,true);
            list($res, $msg) = $itemsModel->saveItems($data, $page_config, $this->admin_uid);
            if (!$res) {
                return callback(400, $msg);
            }
            return callback(200, '保存成功');
        }
        $code = $this->request->param('code/s');
        $linkType = [
            '1' => 'URL链接',
            '2' => '资源分类',
            '3' => '文章分类',
        ];
        $this->assign('link_type', json_encode($linkType, JSON_UNESCAPED_UNICODE));
        $this->assign('page_code', $code);
        $itemModel = new ThemeItems();
        $result = $itemModel->getParams($code, $this->admin_uid);
        $pageConfig = [];
        if ($result['data']) {
            foreach ($result['data']['items'] as $key => $value) {
                $pageConfig[$key]['type'] = $value['widget_code'];
                $pageConfig[$key]['value'] = $value['params'];
            }
        }
        $pageConfig = json_encode($pageConfig, 320);
        $this->assign('page_config', $pageConfig);
        //取出所有分类
        $sortModel = new ResourceSort();
        $sort_list = ResourceSort::where(['status' => 1])->field('id,pid,name')->order('indexid asc,id asc')->select()->toArray();
        $tree = new Tree();
        $tree->init($sort_list, 'pid');
        $sortList = $tree->getTreeArray(0);
        $this->assign('sortList', json_encode($sortList, JSON_UNESCAPED_UNICODE));
        //取出资源一级分类
        $topSortList = $sortModel->getChildSort();
        $this->assign('topSortList', json_encode($topSortList, JSON_UNESCAPED_UNICODE));

        //获取友情链接
        $linkList=\app\common\model\Links::where('status',1)->order('indexid asc,id asc')->select();
        $this->assign('linkList', json_encode($linkList, JSON_UNESCAPED_UNICODE));
        //文章分类
        $articleSort = new \app\common\model\ArticleSort();
        $this->assign('articleSortList', json_encode($articleSort->getTree(), JSON_UNESCAPED_UNICODE));
        return $this->view->fetch('theme');
    }

    /**
     * 添加布局
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    $res = $this->model->where('code', $params['code'])->find();
                    if (!empty($res)) {
                        return callback(404, '编码已经存在，请换一个');
                    }
                    $params['type'] = 2;
                    $params['layout'] = 1;
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '添加成功');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                    ThemeItems::where('page_code', $v['code'])->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($count) {
                return callback(200, '删除成功');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }
}
