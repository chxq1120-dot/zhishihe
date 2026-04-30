<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\model;

use think\Db;
use think\Exception;
use think\Model;
use zp\Tree;

class ThemeItems extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text'
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }

    /**
     * 关联
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id')->setEagerlyType(0)->joinType('left');
    }

    /**
     * 关联首页模板
     * @return \think\model\relation\BelongsTo
     */
    public function theme()
    {
        return $this->belongsTo('Theme', 'page_code', 'code')->setEagerlyType(0);
    }

    /**
     * 获取页面配置数据
     * @param string $page_code
     * @param string $token
     * @return array
     */
    public function getParams($page_code, $admin_id = 1)
    {
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => [],
        ];
        $theme = new Theme();
        $themeInfo = $theme->where([['code', '=', $page_code]])->find();
        $themeItemsModel = new ThemeItems();
        $data = $themeItemsModel->where('page_code', '=', $page_code)->order('sort asc')->select();
        if ($data->isEmpty()) {
            $result['msg'] = '请先配置该页面';
            return $result;
        }
        try {
            $data = $data->toArray();
            $i = 0;
            foreach ($data as $key => $value) {
                $data[$i]['params'] = json_decode($value['params'], true);
                if ($value['widget_code'] == 'notice') {
                    if ($data[$i]['params']['type'] == 'auto') {
                        $noticeModel = new Article();
                        $list = $noticeModel->getList();
                        $data[$i]['params']['list'] = $list;
                    }
                } elseif ($value['widget_code'] == 'resource') {
                    $where = [];
                    $spreadModel = new Spread();
                    if ($data[$i]['params']['type'] == 'auto') {
                        //资源分类,同时取所有子分类 todo 无限极分类时要注意
                        $field='a.id,a.title,a.thumb,a.price,a.dis_price,a.sales,a.level,a.type,a.utime,a.ctime,a.is_vip';
                        $spreads = $spreadModel->alias('a')->field($field);
                        $data[$i]['params']['classifyId'] = intval($data[$i]['params']['classifyId']);
                        if (isset($data[$i]['params']['classifyId']) && $data[$i]['params']['classifyId']) {
                            $sortModel = new ResourceSort();
                            $sort_arr = $sortModel->where('status', 1)->order('indexid asc,id asc')->select();
                            if ($sort_arr) {
                                $sort_arr = $sort_arr->toArray();
                                $tree = new Tree();
                                $tree->init($sort_arr, 'pid');
                                $child_ids = $tree->getChildrenIds($data[$i]['params']['classifyId'], true);
                                $spreads->join('spread_type b', 'a.id=b.rid');
                                $where[] = ['b.sid', 'in', $child_ids];
                            }
                        }
                        //推荐
                        if (isset($data[$i]['params']['is_top']) && $data[$i]['params']['is_top'] == 1) {
                            $where[] = ['a.is_top', 'eq', $data[$i]['params']['is_top']];
                        }
                        $where[] = ['a.admin_id', 'eq', $admin_id];
                        $where[] = ['a.status', 'eq', 1];
                        $limit = isset($data[$i]['params']['limit']) ? $data[$i]['params']['limit'] : 20;
                        if($limit>100) $limit=100;
                        $spread_list=[];
                        $spreads = $spreads->where($where)->order('a.utime desc,a.ctime desc')->group('a.id')->limit($limit)->select();
                        if(!$spreads->isEmpty()){
                            $spread_list = $spreads->toArray();
                        }
                        $data[$i]['params']['list'] = $spread_list;
                    } else {
                        foreach ((array)$data[$i]['params']['list'] as $gk => $gv) {
                            $spreads = $spreadModel->getDetials($gv['id'], 'id,title,thumb,price,dis_price,sales,level,type,utime,ctime,is_vip');
                            $data[$i]['params']['list'][$gk] = $spreads['data'];
                        }
                    }
                } elseif ($value['widget_code'] == 'articleList') {
                    $article = new Article();
                    $type_id = $data[$i]['params']['articleSortId'];
                    $limit = $data[$i]['params']['limit'];
                    $list = $article->getList($type_id, 'ctime', 'desc', 1, $limit);
                    $data[$i]['params']['list'] = $list;
                } elseif ($value['widget_code'] == 'textarea') {
                    $data[$i]['params'] = clearHtml($data[$i]['params'], ['width', 'height']);//清除文章中宽高
                    $data[$i]['params'] = str_replace("<img", "<img style='max-width: 100%'", $data[$i]['params']);
                } elseif ($value['widget_code'] == 'resourceSort') {
                    if ($data[$i]['params']['type'] == 'auto'){
                        $sortModel = new ResourceSort();
                        $sortList = $sortModel->getChildSort(0, $data[$i]['params']['limit']);
                        foreach ($sortList as $k => $v) {
                            if (empty($v['thumb'])) {
                                $sortList[$k]['thumb'] = request()->domain() . '/static/admin/custom/images/empty.png';
                            }
                        }
                    }else{
                        $sortList=$data[$i]['params']['list'];
                    }
                    $data[$i]['params']['list'] = $sortList;
                }
                $i++;
            }
        } catch (Exception $e) {
            $result['status'] = false;
            $result['msg'] = $e->getMessage();
            doSyslog($e->getMessage(),'themeItems');
            doSyslog($e->getTraceAsString(),'themeItems');
            return $result;
        }
        $themeInfo['items'] = $data;
        $result['data'] = $themeInfo;
        return $result;
    }

    /**
     * 获取指定部件配置
     * @param $page_code
     * @param int $admin_id
     * @param string $widget_code
     * @return array
     */
    public function getWidget($page_code, $admin_id = 1, $widget_code = 'tabbar')
    {
        $data = $this->where(['page_code' => $page_code, 'admin_id' => $admin_id, 'widget_code' => $widget_code])->order('sort asc')->find();
        if (empty($data)) {
            return [false, '请先配置该页面'];
        }
        $data = $data->toArray();
        $params = json_decode($data['params'], true);
        return [true, $params];
    }

    /**
     * 保存页面布局
     * @param $data array 配置数据
     * @param $page_config array 页面编码
     * @param $admin_id int 代理ID
     * @param $layout int 布局类型，1首页，2主题页
     * @return array
     * @throws \Exception
     */
    public function saveItems($data, $page_config, $admin_id, $layout = 1)
    {
        Db::startTrans();
        $theme = Theme::where('code', $page_config['code'])->find();
        if (empty($theme)) {
            $item = [
                'name' => $page_config['name'],
                'code' => $page_config['code'],
                'admin_id' => $admin_id,
                'type' => 1,
                'layout' => $layout,
                'ctime' => time()
            ];
            $theme=Theme::create($item, true);
        } else {
            $theme->save(['name'=>$page_config['name'],'utime'=>time()]);
        }
        $this->where([['page_code', '=', $theme->code]])->delete();//先删除
        $iData = [];
        foreach ($data as $key => $value) {
            $iData[] = [
                'admin_id' => $admin_id,
                'widget_code' => $value['type'],
                'page_code' => $theme->code,
                'position_id' => $key,
                'sort' => $key + 1,
                'params' => json_encode($value['value'], JSON_UNESCAPED_UNICODE),
                'ctime' => time()
            ];
        }
        if (!$this->saveAll($iData)) {
            Db::rollback();
            return [false, '保存失败'];
        }
        Db::commit();
        return [true, '保存成功'];
    }
}