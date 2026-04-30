<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\AdminSite;
use app\common\model\PosterConfig;
use app\common\model\ResourceSort;
use app\common\model\Theme;
use app\common\model\ThemeConfig;
use app\common\model\ThemeItems;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use zp\Tree;

class Cuscom extends Common
{
    protected $sceneTag = 'Theme';
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'admin_id';

    public function initialize()
    {
        parent::initialize();
        $this->model = new Theme();
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
                ->where('type', 1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('type', 1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        #首页预览地址
        $h5path = env('root_path') . 'frontend' . DIRECTORY_SEPARATOR . 'h5';
        if (is_dir($h5path)) {
            $site_url = (new AdminSite())->getSiteUrl($this->admin_uid);
            $home_url = $site_url;
            $special_id = Theme::where(['layout' => 2, 'type' => 1])->order('id asc')->value('id');
            $special_url = '';
            if (!empty($special_id)) {
                $special_url = $site_url . '/#/custom/subject/index?id=' . $special_id;
            }
        } else {
            $home_url = '';
            $special_url = '';
        }
        $this->view->assign('home_url', $home_url);
        $this->view->assign('special_url', $special_url);
        #读取视图默认ID
        $view_id = Admin::where('id', $this->admin_uid)->value('view_id');
        $this->view->assign('view_id', $view_id);
        #读取个人中心配置
        $userConfig = json_encode([
            "topBgcolor" => [
                "start" => "#FDA451",
                "end" => "#FFC037"
            ],
            "topTextColor" => "#000000",
            "cardStyle" => "style1",
            "list" => [
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/018272d431f75e36c493d30d49aff686.png",
                    "name" => "我的资源",
                    "link" => "/pages/my/resource"
                ],
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/f447dd9726269c1a51e9b0acc0297405.png",
                    "name" => "我的团队",
                    "link" => "/pages/my/team"
                ],
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/6569a2166ae4406df75349b7b58910a0.png",
                    "name" => "我的钱袋",
                    "link" => "/pages/my/wallet"
                ],
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/931d81f6dd313d74c426bf971dc70e38.png",
                    "name" => "我的收藏",
                    "link" => "/pages/my/favs"
                ],
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/198d36f49a6d2c882bdb3c41ee97c945.png",
                    "name" => "帮助中心",
                    "link" => "/pages/article/list?sort_id=2"
                ],
                [
                    "image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20240124/2333d2260886b402dec66a9fadcaf0b8.png",
                    "name" => "联系我们",
                    "link" => "/pages/my/contact"
                ]
            ]
        ]);
        $config = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 1])->find();
        if (!empty($config)) {
            $userConfig = $config->params;
        }
        $this->view->assign('userConfig', $userConfig);
        #读取分类配置
        $sortConfig = json_encode([
            'search' => [
                'style' => 'style1',
                'placeholder' => '点击搜索资源'
            ],
            'style' => 'style1',
            'color' => [
                'bg' => '#FFF3E9',
                'text' => '#FF6E3E'
            ],
            'isTop' => 0,
            'limit' => 10
        ]);
        $config = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 2])->find();
        if (!empty($config)) {
            $sortConfig = $config->params;
        }
        $this->view->assign('sortConfig', $sortConfig);
        #获取分类
        $list = (new ResourceSort)->where('status', 1)->field('id,pid,thumb,name')->order('indexid asc,id asc')->select();
        $tree = new Tree();
        $tree->init($list, 'pid', '', 'children');
        $sortList = $tree->getTreeArray('pid');
        $this->view->assign('sortList', json_encode($sortList));
        #读取最新的资源
        $resource = \app\common\model\Spread::where(['status'=>1,'admin_id'=>$this->admin_uid])->field('id,title,thumb,price,dis_price,sales,level')->order('utime desc,ctime desc')->limit(10)->select();
        foreach ($resource as $k => $v) {
            $resource[$k]['level_name'] = getLevelLabel($v['level']);
        }
        $this->view->assign('resource', json_encode($resource));
        return $this->view->fetch();
    }

    /**
     * 模板切换
     */
    public function changeTemp()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->post("id/d", 0);
            if (empty($ids)) {
                return callback(404, '请选择布局');
            }
            $res = Admin::where('id', $this->admin_uid)->update(['view_id' => $ids]);
            if (!$res) {
                return callback(404, '布局切换失败');
            }
            return callback(200, '切换成功');
        }
    }

    /**
     * 自定义海报
     */
    public function poster()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param('row/a');
            if (empty($params)) {
                return callback(400, '保存失败');
            }
            $config = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => $params['type']])->find();
            if (!empty($config)) {
                $config->setting = json_encode($params);
                $config->utime = time();
                $result = $config->save();
            } else {
                $data = [
                    'type' => $params['type'],
                    'admin_id' => $this->admin_uid,
                    'setting' => json_encode($params),
                    'ctime' => time()
                ];
                $result = PosterConfig::create($data, true);
            }
            if (!$result) {
                return callback(400, '保存失败');
            }
            return callback(200, '保存成功');
        }
        $data = [
            'poster1' => [
                'bg_color' => 'rgb(254,167,0)',
                'main_text' => '给您分享了一个好资源，扫码完成任务可免费领取',
                'bg_main_color' => 'rgb(255,255,255)',
                'sub_text' => '免费好资源就等你来',
            ],
            'poster2' => [
                'main_text' => '邀您免费学习好课程',
                'main_text_color' => 'rgb(255,255,255)',
                'main_text_size' => '20',
                'sub_text' => '百套课程资源免费领取 分享赚佣金学习两不误',
                'url' => $this->request->domain() . '/static/common/images/poster_bg.jpg',
                'sub_text_color' => 'rgb(255,255,255)',
                'sub_text_size' => '22',
            ],
            'poster3' => [
                'url' => $this->request->domain() . '/static/common/images/poster_agent.jpg',
                'bom_text' => '现在永远是好的创业时机',
                'bom_text_size' => '16',
                'bom_text_color' => 'rgb(0,0,0)'
            ],
        ];
        $poster1 = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => 1])->find();
        if (!empty($poster1)) {
            $data['poster1'] = json_decode($poster1->setting, true);
        }
        $poster2 = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => 2])->find();
        if (!empty($poster2)) {
            $data['poster2'] = json_decode($poster2->setting, true);
        }
        $poster3 = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => 3])->find();
        if (!empty($poster3)) {
            $data['poster3'] = json_decode($poster3->setting, true);
        }
        $sub_text = explode(' ', $data['poster2']['sub_text']);
        $this->view->assign('sub_text', $sub_text);
        $this->view->assign('row', $data);
        return $this->view->fetch('poster');
    }

    /**
     * 自定义移动端首页布局
     */
    public function saveTheme()
    {
        if ($this->request->isAjax()) {
            $data = input('post.data/s');
            if (empty($data)) {
                $data = [];
            } else {
                $data = json_decode($data, true);
            }
            $page_config = input('post.pageConfig/a', []);
            if (empty($page_config)) {
                return callback(400, '请设置模板名称');
            }
            $layout = input('post.layout/d', 1);
            $itemsModel = new ThemeItems();
            list($res, $msg) = $itemsModel->saveItems($data, $page_config, $this->admin_uid, $layout);
            if (!$res) {
                return callback(400, $msg);
            }
            return callback(200, '保存成功');
        }
        $layout = $this->request->param('layout/d', 1);
        $code = $this->request->param('code/s');
        if (empty($code)) {
            $code = 'mobile_' . mt_rand(11111, 99999);
            $page_title = '新页面';
        } else {
            $themeConfig = Theme::where('code', $code)->find();
            if ($themeConfig) {
                $code = $themeConfig->code;
                $page_title = $themeConfig->name;
            } else {
                $code = 'mobile_' . mt_rand(11111, 99999);
                $page_title = '新页面';
            }
        }
        $linkType = [
            '1' => 'URL链接',
            '2' => '课程资源',
            '3' => '资源分类',
            '4' => '文章信息',
            '5' => '文章分类',
            '6' => '前端页面',
            '7' => '外部小程序',
        ];
        $this->assign('link_type', json_encode($linkType, JSON_UNESCAPED_UNICODE));
        $this->assign('page_code', $code);
        $this->assign('page_title', $page_title);
        $this->assign('layout', $layout);
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
        $sort_list = $sortModel->where(['status' => 1])->field('id,pid,name')->order('indexid asc,id asc')->select()->toArray();
        $tree = new Tree();
        $tree->init($sort_list, 'pid', '', 'child');
        $sortList = $tree->getTreeArray('pid');
        $this->assign('sortList', json_encode($sortList, JSON_UNESCAPED_UNICODE));

        //取出资源一级分类
        $topSortList = $sortModel->getChildSort();
        $this->assign('topSortList', json_encode($topSortList, JSON_UNESCAPED_UNICODE));

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
                    $params['type'] = 1;
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

    /**
     * 主题风格
     */
    public function setting()
    {
        if ($this->request->isAjax()) {
            try {
                $data = $this->request->param('data/a');
                if (empty($data)) {
                    return callback(400, '请设置主题颜色');
                }
                Db::startTrans();
                $theme = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 0])->find();
                if (empty($theme)) {
                    $theme_param = [
                        'name' => $data['name'],
                        'page' => 0,
                        'admin_id' => $this->admin_uid,
                        'params' => json_encode($data),
                        'ctime' => time()
                    ];
                    $result = ThemeConfig::create($theme_param, true);
                    if (!$result) {
                        Db::rollback();
                        return callback(400, '保存失败');
                    }
                    #替换底部导航
                    if ($data['name'] !== 'diy') {
                        $result = $this->replaceTheme($this->admin_uid, $data);
                        if (!$result) {
                            Db::rollback();
                            return callback(400, '保存失败');
                        }
                    }
                    Db::commit();
                    return callback(200, '保存成功');
                } else {
                    $theme->name = $data['name'];
                    $theme->params = json_encode($data);
                    $theme->utime = time();
                    $result = $theme->save();
                    if (!$result) {
                        return callback(400, '保存失败');
                    }
                    #替换底部导航
                    if ($data['name'] !== 'diy') {
                        $result = $this->replaceTheme($this->admin_uid, $data);
                        if (!$result) {
                            Db::rollback();
                            return callback(400, '保存失败');
                        }
                    }
                    Db::commit();
                    return callback(200, '保存成功');
                }
            } catch (Exception $e) {
                return callback(400, $e->getMessage());
            }
        }
        // 默认
        $params = [
            'title' => '默认主题',
            'name' => 'default',
            'themeColor' => [
                'nav_bg_color' => '#ffc037',
                'nav_text_color' => '#000000',
                'home_bg_color' => '#ffc037',
                'svip_bg_color' => '#ffc037',
                'team_bg_color' => '#ffc037',
                'tab_bg_color' => '#ffffff',
                'tab_text_color' => '#999999',
                'tab_text_color_on' => '#ffc037',
                'tab_border_color' => 'black',
                'show_nav_bg_color' => '#000000',
                'show_nav_text_color' => '#ffffff',
                'show_top_bg_color' => '#ffffff',
                'show_top_text_color' => '#000000',
            ],
            'tabbar' => [
                "limit" => "5",
                "list" => [
                    ["image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/default/home.png", "image_on" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/yellow/home.png", "text" => "首页", "linkType" => "6", "linkValue" => "/pages/tabbar/home/index"],
                    ["image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/default/sort.png", "image_on" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/yellow/sort.png", "text" => "课程", "linkType" => "6", "linkValue" => "/pages/tabbar/sort/index"],
                    ["image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/default/vip.png", "image_on" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/yellow/vip.png", "text" => "会员", "linkType" => "6", "linkValue" => "/pages/tabbar/vip/index"],
                    ["image" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/default/my.png", "image_on" => "https://zpcms.oss-cn-beijing.aliyuncs.com/icon/tabbar/yellow/my.png", "text" => "我的", "linkType" => "6", "linkValue" => "/pages/tabbar/my/index"]
                ]
            ],
            'maincolor' => '#ffc037',
            'subcolor' => '#ffac00',
            'textcolor' => '#2c2c2c',
            'thumb' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/icon/theme/default.jpg',
        ];
        $theme_param = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 0])->value('params');
        if (!empty($theme_param)) {
            $params = json_decode($theme_param, true);
        }
        $this->assign('row', json_encode($params));
        return $this->view->fetch();
    }

    /**
     * 根据主题替换首页布局底部导航
     */
    protected function replaceTheme($admin_uid, $params)
    {
        if (empty($params['tabbar'])) {
            return false;
        }
        $mobile_theme = Theme::alias('a')->join('theme_items b', 'a.code=b.page_code')->where(['a.admin_id' => $admin_uid, 'a.layout' => 1, 'a.type' => 1, 'b.widget_code' => 'tabbar'])->field('b.id')->select();
        foreach ($mobile_theme as $theme) {
            ThemeItems::where(['id' => $theme['id'], 'admin_id' => $admin_uid])->update(['params' => json_encode($params['tabbar'])]);
        }
        #替换自定义我的头部样式
        $theme_my = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 1])->find();
        if (!empty($theme_my)) {
            $data = json_decode($theme_my->params, true);
            $data['topBgcolor']['start'] = $params['maincolor'];
            $data['topBgcolor']['end'] = $params['subcolor'];
            $theme_my->params = json_encode($data);
            $theme_my->ctime = time();
            $theme_my->save();
        }
        return true;
    }
    /**
     * 保存个人中心设置
     * @return mixed
     */
    public function saveUserConfig()
    {
        try {
            if ($this->request->isAjax()) {
                $data = $this->request->post('data/s');
                if (empty($data)) {
                    return callback(400, '保存失败');
                }
                $config = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 1])->find();
                if (!empty($config)) {
                    $config->utime = time();
                    $config->params = $data;
                    $result = $config->save();
                } else {
                    $params = [
                        'admin_id' => $this->admin_uid,
                        'page' => 1,
                        'params' => $data,
                        'ctime' => time()
                    ];
                    $result = ThemeConfig::create($params, true);
                }
                if (!$result) {
                    return callback(400, '保存失败');
                }
                return callback(200, '保存成功');
            }
        } catch (Exception $e) {
            return callback(400, $e->getMessage());
        }
    }

    /**
     * 保存分类页面设置
     * @return mixed
     */
    public function saveSortConfig()
    {
        try {
            if ($this->request->isAjax()) {
                $data = $this->request->post('data/s');
                if (empty($data)) {
                    return callback(400, '保存失败');
                }
                $config = ThemeConfig::where(['admin_id' => $this->admin_uid, 'page' => 2])->find();
                if (!empty($config)) {
                    $config->utime = time();
                    $config->params = $data;
                    $result = $config->save();
                } else {
                    $params = [
                        'admin_id' => $this->admin_uid,
                        'page' => 2,
                        'params' => $data,
                        'ctime' => time()
                    ];
                    $result = ThemeConfig::create($params, true);
                }
                if (!$result) {
                    return callback(400, '保存失败');
                }
                return callback(200, '保存成功');
            }
        } catch (Exception $e) {
            return callback(400, $e->getMessage());
        }
    }
}