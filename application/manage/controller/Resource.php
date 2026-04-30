<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Resource as ResourceModel;
use app\common\model\ResourceLevel;
use app\common\model\ResourceType;
use app\common\model\ResourceSort;
use app\common\model\Spread;
use app\common\model\SpreadType;
use app\common\model\ResourceKammi;
use think\Db;
use app\common\model\VideoCourse;
use app\common\model\AudioCourse;
use app\common\model\ResourceInfo;
use EasyWeChat\Kernel\Messages\Video;
use think\Exception;
use think\facade\Env;
use zp\Tree;

class Resource extends Common
{
    protected $searchFields = 'resource.title,resource.desc';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'resource';
    protected $dataLimit = true;
    protected $dataLimitField = 'admin_id';
    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\Resource();
    }

    public function index()
    {
        if (request()->isAjax()) {
            $data = input('get.');
            $this->searchvar($data);
            $list = ResourceModel::alias('resource')->withSearch(
                ['ctime', 'name', 'status', 'sort'],
                ['ctime' => [$this->start, $this->end],
                    'name' => $this->search,
                    'status' => $this->status,
                    'sort' => $this->sort
                ]);
            $list->with(['agent' => ['id','group_id', 'username'],'user' => ['id','nickname']]);
            $list = $list->order('resource.id desc')->group('resource.id')->paginate(['list_rows' => $this->limit, 'page' => $this->page])->toArray();
            $resource_rid=array_column($list['data'], 'id');
            $spread = Spread::where('admin_id', $this->admin_uid)->where('rid','in',$resource_rid)->column('rid');
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['spread'] = 0;
                if (in_array($v['id'], $spread)) {
                    $list['data'][$k]['spread'] = 1;
                }
                $sorts = ResourceType::alias('a')->join('resource_sort b', 'a.sid=b.id')->where('a.rid', $v['id'])->column('b.name');
                $list['data'][$k]['sort_name'] = $sorts;
            }
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        $sort_list = ResourceSort::where(['status' => 1])->field('id,pid,name')->order('indexid asc,id asc')->select()->toArray();
        $tree = new Tree();
        $tree->init($sort_list, 'pid');
        $tree_list = $tree->getTree(0);
        $this->assign('sortSelect', $tree_list);
        return $this->view->fetch();

    }

    /**
     * 添加资源
     */
    public function add()
    {
        if (request()->isAjax()) {
            Db::startTrans();
            $data = input('post.');
            if (!isset($data['sort_id'])) {
                return callback(400, '请选择资源分类');
            }
            $data['admin_id'] = $this->admin_uid;
            #网盘资源
            if ($data['type'] == 1) {
                if (empty($data['link'])) {
                    return callback(400, '请输入网盘链接');
                }
            }
            if ($data['type'] == 3) {
                $data['ext_code'] = txtractLink($data['link']);
            }
            if(empty($data['content'])){
                return callback(400, '请输入资源详情');
            }
            #保存资源
            $resource=$this->model->create($data,true);
            $data['sort_id'] = explode(',', $data['sort_id']);
            if ($resource->id) { //插入分类关联数据
                $arr = [];
                foreach ($data['sort_id'] as $key => $value) {
                    $arr[] = ['rid' => $resource->id, 'sid' => $value];
                }
                if (!(new ResourceType)->saveAll($arr)) {
                    Db::rollback();
                    return callback(400, '添加失败');
                }
                //课程章节
                if ($data['type'] == 4 || $data['type'] == 5) {
                    if (!empty($data['kcinfo'])) {
                        $kcarr = [];
                        foreach ($data['kcinfo'] as $k => $v) {
                            $kcarr[] = [
                                'title' => $v['title'],
                                'url' => $v['url'],
                                'times' => $v['times'],
                                'is_try' => $v['is_try'],
                                'indexid' => ($k + 1),
                                'rid' => $resource->id,
                            ];
                        }
                        if ($data['type'] == 4) {
                            if (!(new VideoCourse)->saveAll($kcarr)) {
                                Db::rollback();
                                return callback(400, '添加失败');
                            }
                        }
                        if ($data['type'] == 5) {
                            if (!(new AudioCourse)->saveAll($kcarr)) {
                                Db::rollback();
                                return callback(400, '添加失败');
                            }
                        }
                    }
                }
                //卡密资源处理
                if ($data['type'] == 6 && !empty($data['kammi'])) {
                    $data['kammi'] = explode("\n", $data['kammi']);
                    $kammi = [];
                    foreach ($data['kammi'] as $value) {
                        $kammi[] = ['rid' => $resource->id, 'cdkey' => $value];
                    }
                    if (!(new ResourceKammi)->saveAll($kammi)) {
                        Db::rollback();
                        return callback(400, '添加失败2');
                    }
                }
                //处理关联资源详情
                if(!empty($data['content'])){
                    $infos=[
                        'rid'=>$resource->id,
                        'content'=>$data['content'],
                        'free_content'=>$data['free_content'],
                    ];
                    if (!(new ResourceInfo())::create($infos,true)) {
                        Db::rollback();
                        return callback(400, '添加失败2');
                    }
                }
                Db::commit();
                return callback(200, '添加成功');
            } else {
                Db::rollback();
                return callback(400, '添加失败');
            }
        }
        $level = ResourceLevel::where('status', 1)->order('indexid asc')->select();
        $this->assign('levels', $level);
        return $this->view->fetch();
    }
    //获取分类数据
    public function gettreesort()
    {
        $id = input('ids');
        $type = [];
        if ($id) {
            $type = ResourceType::where('rid', $id)->column('sid');
        }
        $sort = ResourceSort::where('status', 1)
            ->field('id,pid,name')
            ->order('indexid asc')
            ->select()->toArray();
        $sort = array_column($sort, null, 'id');
        $sort = $this->treesort($sort, $type);
        return json($sort);
    }

    //组装树形组件数据
    function treesort($list, $gauth = [])
    {
        $tree = array();
        foreach ($list as $k => $v) {
            $list[$k]['title'] = $v['name'];
            $list[$k]['checked'] = in_array($v['id'], $gauth) ? true : false;
            if (!empty($v['pid'])) {//树形组件判断BUG需要
                $list[$v['pid']]['checked'] = null;
            }
            if (isset($list[$v['pid']])) {
                $list[$v['pid']]['children'][] = &$list[$k];
            } else {
                $tree[] = &$list[$k];
            }
        }
        return $tree;
    }

    /**
     * 编辑资源
     * @return [type] [description]
     */
    public function edit($ids = null)
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $type = ResourceType::where('rid', $ids)->select()->toArray();
            $type = array_column($type, null, 'sid');
            $resource = new ResourceModel;
            if (!isset($data['sort_id'])) {
                return callback(400, '请选择资源分类');
            }
            #网盘资源
            if ($data['type'] == 1) {
                if (empty($data['link'])) {
                    return callback(400, '请输入网盘链接');
                }
            }
            #文章文档
            if ($data['type'] == 3) {
                $data['ext_code'] = txtractLink($data['link']);
            }
            $resource->allowField(true)->save($data, ['id' => $ids]);
            $data['sort_id'] = explode(',', $data['sort_id']);
            foreach ($data['sort_id'] as $key => $value) {
                if (isset($type[$value])) {
                    unset($type[$value]);
                } else {
                    ResourceType::create(['rid' => $ids, 'sid' => $value]);
                }
            }
            //清除原有的分类关联
            if (!empty($type)) {
                foreach ($type as $key => $value) {
                    ResourceType::destroy($value['id']);
                }
            }
            //处理关联资源详情
            if(!empty($data['content'])){
                $infos=[
                    'content'=>$data['content'],
                    'free_content'=>$data['free_content'],
                ];
                if (!(new ResourceInfo())->allowField(true)->save($infos,['rid'=> $ids])) {
                    Db::rollback();
                    return callback(400, '详情更新失败');
                }
            }
            return callback(200, '保存成功');
        }
        $info = ResourceModel::with(['info'=>['free_content','content']])->where('resource.id', $ids)->find();
        $this->assign(['info' => $info]);
        $level = ResourceLevel::where('status', 1)->order('indexid asc')->select();
        $this->assign('levels', $level);
        return $this->fetch();
    }

    /**
     * 删除资源
     * @return [type] [description]
     */
    public function del($ids = '')
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            $where[]=[$pk, 'in', $ids];
            if (is_array($adminIds)) {
                $where[]=[$this->dataLimitField, 'in', $adminIds];
            }
            $list = $this->model->where($where)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                    ResourceType::where('rid', $v->id)->delete();
                    if ($v->type == 4) {
                        VideoCourse::where('rid', $v->id)->delete();
                    }
                    if ($v->type == 5) {
                        AudioCourse::where('rid', $v->id)->delete();
                    }
                    if ($v->type == 6) {
                        ResourceKammi::where('rid', $v->id)->delete();
                    }
                    #删除推广中心资源
                    Spread::where('rid',$v->id)->delete();
                    #删除资源详情
                    ResourceInfo::where('rid',$v->id)->delete();
                }
                Db::commit();
            } catch (\think\exception\PDOException $e) {
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
     * 设置资源状态
     * @return [type] [description]
     */
    public function setstatus()
    {
        if (request()->isAjax()) {
            $ids = input('ids');
            $ids = explode(',', $ids);
            $status = input('status', 0);
            ResourceModel::where('id', 'in', $ids)->update(['status' => $status]);
            Spread::where('rid', 'in', $ids)->update(['status' => $status]);
            return callback(200, '操作成功');
        }
    }

    /**
     * 批量设置分类
     */
    public function setsort()
    {
        if (request()->isAjax()) {
            $ids = $this->request->param('ids');
            $sort_id = $this->request->param('sort_id');
            if (empty($ids)) {
                return callback(400, '请选择要设置分类的资源');
            }
            if (empty($sort_id)) {
                return callback(400, '请选择资源分类');
            }
            $sort_id = explode(',', $sort_id);
            $ids = explode(',', $ids);
            $resType = new ResourceType();
            foreach ($ids as $id) {
                $types = $resType->where('rid', $id)->select()->toArray();
                $types = array_column($types, null, 'sid');
                foreach ($sort_id as $key => $value) {
                    if (isset($types[$value])) {
                        unset($types[$value]);
                    } else {
                        $resType->create(['rid' => $id, 'sid' => $value]);
                    }
                }
                if (!empty($types)) {
                    foreach ($types as $key => $value) {
                        $resType->destroy($value['id']);
                    }
                }
            }
            return callback(200, '操作成功');
        }
        return view();
    }
    /**
     * 复制资源
     * @return [type] [description]
     */
    public function copys()
    {
        try{
            if (request()->isAjax()) {
                $ids = $this->request->param('ids');
                if(empty($ids)){
                    return callback(400, '请选择要复制的资源');
                }
                $resources=$this->model->where('id', 'in', $ids)->select();
                foreach ($resources as $resource) {
                    $resource_arr = $resource->toArray();
                    $resource_id = $resource_arr['id'];
                    unset($resource_arr['id']);
                    $resource_arr['utime'] = time();
                    $resource_arr['ctime'] = time();
                    $newResource = $this->model->create($resource_arr, true);
                    if (!$newResource){
                        continue;
                    }
                    $resourcetype = ResourceType::where('rid', $resource_id)->select();
                    if ($resourcetype) {
                        foreach ($resourcetype as $k => $v) {
                            ResourceType::create(['rid' => $newResource->id, 'sid' => $v['sid']]);
                        }
                    }
                    //处理课程资源复制
                    if ($resource_arr['type'] == 4 || $resource_arr['type'] == 5) {
                        if($resource_arr['type'] == 4){
                            $courses=VideoCourse::where('rid', $resource_id)->select();
                            if (!empty($courses)) {
                                $courses=$courses->toArray();
                                foreach ($courses as $course) {
                                    unset($course['id']);
                                    $course['rid']=$newResource->id;
                                    $course['ctime']=strtotime($course['ctime']);
                                    VideoCourse::create($course,true);
                                }
                            }
                        }else{
                            $courses=AudioCourse::where('rid', $resource_id)->select();
                            if (!empty($courses)) {
                                $courses=$courses->toArray();
                                foreach ($courses as $course) {
                                    unset($course['id']);
                                    $course['rid']=$newResource->id;
                                    $course['ctime']=strtotime($course['ctime']);
                                    AudioCourse::create($course,true);
                                }
                            }
                        }
                    }
                    //处理资源详情
                    $resource_info=ResourceInfo::where('rid', $resource_id)->find();
                    if(!empty($resource_info)){
                        $infos=[
                            'rid'=>$newResource->id,
                            'content'=>$resource_info['content'],
                            'free_content'=>$resource_info['free_content'],
                        ];
                        (new ResourceInfo())->create($infos,true);
                    }
                }
                return callback(200, '操作成功');
            }
        }catch(\Exception $e){
            return callback(400, $e->getMessage());
        }
    }
    /**
     * 推广
     * @return [type] [description]
     */
    public function spread()
    {
        if (request()->isAjax()) {
            $ids = $this->request->param('ids');
            $type = $this->request->param('type/d', 0);
            $where = [['id', 'in', $ids], ['status', '=', 1]];
            if ($type == 1) {//一键推广
                $where = ['status' => 1];
            }
            $field = 'id,admin_id,title,thumb,link,ext_code,type,price,dis_price,desc,sales,level,invite,invite_num,exc_video,is_fenxiao,sell_set,sell_type,sell,sell_type2,sell2';
            $resources=ResourceModel::where($where)->field($field)->cursor();
            foreach ($resources as $resource) {
                $spreadInfo = Spread::where(['rid' => $resource->id, 'admin_id' => $this->admin_uid])->find();
                if ($spreadInfo) {//更新
                    $spreadInfo->title = $resource->title;
                    $spreadInfo->thumb = $resource->thumb;
                    $spreadInfo->link = $resource->link;
                    $spreadInfo->ext_code = $resource->ext_code;
                    $spreadInfo->desc = $resource->desc;
                    $spreadInfo->price = $resource->price;
                    $spreadInfo->dis_price = $resource->dis_price;
                    $spreadInfo->invite = $resource->invite;
                    $spreadInfo->invite_num = $resource->invite_num;
                    $spreadInfo->exc_video = $resource->exc_video;
                    $spreadInfo->is_fenxiao = $resource->is_fenxiao;
                    $spreadInfo->sell_set = $resource->sell_set;
                    $spreadInfo->sell_type = $resource->sell_type;
                    $spreadInfo->sell = $resource->sell;
                    $spreadInfo->sell_type2 = $resource->sell_type2;
                    $spreadInfo->sell2 = $resource->sell2;
                    $spreadInfo->level = $resource->level;
                    $spreadInfo->utime = time();
                    $spreadInfo->save();
                    #先删除
                    SpreadType::where(['rid' => $spreadInfo->id])->delete();
                    $resourcetype = ResourceType::where('rid', $resource->id)->select();
                    if ($resourcetype) {
                        foreach ($resourcetype as $k => $v) {
                            SpreadType::create(['rid' => $spreadInfo->id, 'sid' => $v['sid']]);
                        }
                    }
                } else {
                    $resource_arr = $resource->toArray();
                    $resource_id = $resource_arr['id'];
                    unset($resource_arr['id']);
                    $resource_arr['rid'] = $resource_id;
                    $resource_arr['views'] = 0;
                    $resource_arr['favs'] = 0;
                    $resource_arr['admin_id'] = $this->admin_uid;
                    $resource_arr['utime'] = time();
                    $resource_arr['ctime'] = time();
                    $Spread = Spread::create($resource_arr, true);
                    if (!$Spread) {
                        continue;
                    }
                    $resourcetype = ResourceType::where('rid', $resource_id)->select();
                    if ($resourcetype) {
                        foreach ($resourcetype as $k => $v) {
                            SpreadType::create(['rid' => $Spread->id, 'sid' => $v['sid']]);
                        }
                    }
                }
            }
            return callback(200, '操作成功');
        }
    }

    /**
     * 处理批量数组
     * @param $data
     * @return void
     */
    protected function resourceData($data)
    {
        foreach ($data as $value) {
            yield $value;
        }
    }

    /**
     * 批量导入
     * @return [type] [description]
     */
    public function batch()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            $file = request()->file('file');
            $fileInfo = $file->getInfo();
            $type = pathinfo($fileInfo['name'])['extension'];
            if ($data['type'] == 1) { //excel
                if ($type === 'xls' || $type === 'xlsx') {
                    $info = $file->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
                    if ($info) {
                        $filePath = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads/' . $info->getSaveName();
                        $this->readExcel($filePath);
                        return callback(200, '导入完成');
                    } else {
                        return callback(400, $file->getError());
                    }
                } else {
                    return callback(400, '文件类型错误');
                }
            }
        } else {
            return view();
        }
    }
    /**
     * 读取excel
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    public function readExcel($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $work = $spreadsheet->getActiveSheet();
        $highestRow = $work->getHighestRow(); //总行数
        $highestColumn = $work->getHighestColumn(); //总列数
        for ($row = 1; $row <= $highestRow; $row++) {
            $arr['title'] = $work->getCell('A' . $row)->getValue();
            $arr['thumb'] = $work->getCell('B' . $row)->getValue();
            $arr['price'] = $work->getCell('C' . $row)->getValue();
            $arr['dis_price'] = $work->getCell('D' . $row)->getValue();
            $type = $work->getCell('E' . $row)->getValue();
            if ($type === '网盘') {
                $arr['type'] = 1;
                $arr['link'] = $work->getCell('F' . $row)->getValue();
                $arr['ext_code'] = $work->getCell('G' . $row)->getValue();
            } elseif ($type === '视频') {
                $arr['type'] = 2;
                $arr['link'] = $work->getCell('F' . $row)->getValue();
            } elseif ($type === '文章') {
                $arr['type'] = 3;
                $arr['link'] = $work->getCell('F' . $row)->getValue();
                $arr['ext_code'] = txtractLink($arr['link']);#为提取扩展后缀
            } else {
                continue;
            }
            #销量
            $arr['sales'] = mt_rand(15, 88);
            $arr['invite'] = 1;
            $arr['invite_num'] = mt_rand(5, 20);
            $arr['desc'] = $work->getCell('H' . $row)->getValue();
            $arr['free_content'] = $work->getCell('J' . $row)->getValue();
            $arr['content'] = $work->getCell('K' . $row)->getValue();
            $arr['status'] = 1;
            $arr['admin_id'] = $this->admin_uid;
            $arr['utime'] = time();
            $arr['ctime'] = time();
            $info = ResourceModel::create($arr);
            $sort_ids = $work->getCell('I' . $row)->getValue();#分类ID
            $sort_ids = explode(',', $sort_ids);
            foreach ($sort_ids as $sort_id) {
                ResourceType::create(['rid' => $info->id, 'sid' => $sort_id]);
            }
            //写入关联详情数据
            if(!empty($arr['content'])){
                ResourceInfo::create(['rid'=>$info->id,'free_content'=>$arr['free_content'],'content'=>$arr['content']],true);
            }
        }
    }

    /**
     * 卡密列表
     * @return [type] [description]
     */
    public function kammi()
    {
        $data = input('param.');
        $res = ResourceModel::where('id', $data['ids'])->find();
        if (request()->isAjax()) {
            $list = ResourceKammi::alias('a')->field('a.*,b.nickname')
                ->where('a.rid', $data['ids'])
                ->join('user b', 'a.uid=b.id', 'left');
            if (!empty($data['search'])) {
                $list = $list->where('a.cdkey', trim($data['search']));
            }
            $list = $list->order('a.id desc')->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])->toArray();
            return json(["code" => 0, "count" => $list['total'], 'data' => $list['data']]);
        } else {
            $this->assign('res', $res);
            return view();
        }
    }

    /**
     * 添加卡密
     * @return [type] [description]
     */
    public function addkammi()
    {
        $data = input('param.');
        if (request()->isAjax()) {
            if ($data['type'] == 1) {
                if (empty($data['kammi'])) {
                    return callback(400, '卡密不能为空');
                }
                $str = explode("\n", $data['kammi']);
            } elseif ($data['type'] == 2) {
                $file = request()->file('file');
                $info = $file->validate(['ext' => 'txt'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
                if (!$info) {
                    return callback(400, $file->getError());
                }
                $path = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $info->getSaveName();
                $str = file_get_contents($path);
                $str = explode("\r\n", $str);
            }
            if (empty($str)) {
                return callback(400, '获取卡密失败');
            }
            $kammi = [];
            foreach ($str as $value) {
                $kammi[] = ['rid' => $data['rid'], 'cdkey' => $value];
            }
            if (!(new ResourceKammi)->saveAll($kammi)) {
                return callback(400, '添加失败');
            }
            return callback(200, '添加成功');
        } else {
            return view();
        }
    }

    /**
     * 删除卡密
     * @return [type] [description]
     */
    public function delkammi()
    {
        if (request()->isPost()) {
            $id = input('post.ids');
            if (ResourceKammi::destroy($id)) {
                return callback(200, '成功');
            } else {
                return callback(400, '失败');
            }
        }
    }

    /**
     * 增加课程
     * @return [type] [description]
     */
    public function addcourse()
    {
        return view();
    }

    /**
     * 课程列表
     * @return [type] [description]
     */
    public function course()
    {
        $data = input('param.');
        $res = ResourceModel::where('id', $data['ids'])->find();
        if (request()->isAjax()) {

            if ($res->type == 4) {
                $db = new VideoCourse;
            }
            if ($res->type == 5) {
                $db = new AudioCourse;
            }
            $list = $db->where('rid', $data['ids'])
                ->order('indexid desc')
                ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])->toArray();

            return json(["code" => 0, "count" => $list['total'], 'data' => $list['data']]);
        } else {
            $this->assign('res', $res);
            return view();
        }
    }

    /**
     * 保存课程
     * @return [type] [description]
     */
    public function savecourse()
    {
        $data = input('param.');
        $res = ResourceModel::where('id', $data['rid'])->find();
        if ($res->type == 4) {
            $db = new VideoCourse;
        }
        if ($res->type == 5) {
            $db = new AudioCourse;
        }
        if (request()->isAjax()) {
            $arr['title'] = $data['title'];
            $arr['url'] = $data['url'];
            $arr['rid'] = $data['rid'];
            $arr['times'] = $data['times'];
            $arr['is_try'] = $data['is_try'];
            $arr['indexid'] = empty($data['indexid']) ? 0 : $data['indexid'];
            $arr['ctime'] = time();
            if (empty($data['id'])) {
                $i = $db->save($arr);
            } else {
                $i = $db->where('id', $data['id'])->update($arr);
            }
            if ($i) {
                return callback(200, '保存成功');
            } else {
                return callback(400, '保存失败');
            }
        } else {
            if (!empty($data['id'])) {
                $info = $db->where('id', $data['id'])->find();
                $this->assign('info', $info);
            }
            $this->assign('res', $res);
            return view();
        }
    }

    /**
     * 删除课程
     * @return [type] [description]
     */
    public function delcourse()
    {
        $data = input('param.');
        $res = ResourceModel::where('id', $data['rid'])->find();
        if ($res->type == 4) {
            $db = new VideoCourse;
        }
        if ($res->type == 5) {
            $db = new AudioCourse;
        }
        if (request()->isAjax()) {
            $i = $db->where('id', $data['ids'])->delete();
            if ($i) {
                return callback(200, '操作成功');
            } else {
                return callback(400, '操作失败');
            }

        }
    }

    /*=================================资源分类===============================*/

    /**
     * 资源分类列表
     * @return [type] [description]
     */
    public function sort()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if (request()->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->relationSearch = false;
            $this->dataLimit = false;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = ResourceSort::where($where)
                ->order($sort, $order)
                ->count();

            $list = ResourceSort::where($where)
                ->order($sort, 'asc')
                ->limit($offset, $limit)
                ->select();

            $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        } else {
            return $this->fetch();
        }
    }

    public function tsort()
    {
        $pid = input('get.ids');
        if (request()->isAjax()) {
            $list = ResourceSort::where('pid', $pid)->select();
            $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list];
            return json($result);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加分类
     * @return [type] [description]
     */
    public function addsort()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            if (ResourceSort::create($data)) {
                return callback(200, '成功');
            } else {
                return callback(400, '失败');
            }
        } else {
            $sort = ResourceSort::where('status', 1)->field('id,pid,name')->select();
            if (!$sort) {
                $sort_list = [];
            }
            $sort_list = $sort->toArray();
            $tree = new Tree();
            $tree->init($sort_list, 'pid');
            $tree_list = $tree->getTree(0);
            $this->assign('sortSelect', $tree_list);
            return $this->fetch();
        }
    }

    /**
     * 编辑分类
     * @return [type] [description]
     */
    public function editsort()
    {
        $id = input('get.ids');
        if (request()->isAjax()) {
            $data = input('post.');
            if ($data['pid'] == $id) {
                return callback(400, '父级ID不能选择自己');
            }
            if (ResourceSort::where('id', $id)->update($data)) {
                return callback(200, '成功');
            } else {
                return callback(400, '失败');
            }
        }
        $info = ResourceSort::where('id', $id)->find();
        $this->assign('info', $info);
        $sort = ResourceSort::where('status', 1)->select();
        $tree_list = [];
        if ($sort) {
            $sort_list = $sort->toArray();
            $tree = new Tree();
            $tree->init($sort_list, 'pid');
            $tree_list = $tree->getTree(0, '<option value=@id @selected @disabled>@spacer@name</option>', $info->pid, $id);
        }
        $this->assign('sortSelect', $tree_list);
        return $this->fetch();
    }

    /**
     * 删除资源分类
     * @return [type] [description]
     */
    public function delsort()
    {
        if (request()->isPost()) {
            $id = input('post.ids');
            $sort = ResourceSort::where('status', 1)->field('id,pid,name')->select();
            if ($sort) {
                $sort_list = $sort->toArray();
                $tree = new Tree();
                $tree->init($sort_list, 'pid');
                $child_ids = $tree->getChildrenIds($id, true);
                $result = ResourceSort::destroy($child_ids);
                if (!$result) {
                    return callback(400, '删除失败');
                }
                return callback(200, '删除成功');
            }
            return callback(400, '删除失败');
        }
    }
}