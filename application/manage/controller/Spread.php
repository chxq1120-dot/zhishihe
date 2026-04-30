<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\AdminSite;
use app\common\model\PosterConfig;
use app\common\model\Qiniu;
use app\common\model\ResourceLevel;
use app\common\model\{
    Spread as spreadModel,
    ResourceSort,
    SpreadType,
    Groups,
    SpreadGroup,
    SpreadJump,
    Jump,
    ResourceInfo,
};
use app\common\model\WechatKey;
use app\common\model\WechatRece;
use app\common\model\WechatSub;
use EasyWeChat\Kernel\Exceptions\HttpException;
use oss\Alioss;
use oss\Qcloud;
use EasyWeChat\Kernel\Support\File;
use PosterMaker\PosterMaker;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use Naixiaoxin\ThinkWechat\Facade;
use GuzzleHttp\Client;
use think\exception\ValidateException;
use think\facade\Env;
use zp\Tree;

class Spread extends Common
{
    protected $searchFields = 'spread.title';
    protected $dataLimit = 'auth';
    public function initialize()
    {
        parent::initialize();
        $this->model = new spreadModel();
    }
    /**
     * 推广中心资源列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $sort_id=0;
            $filter = $this->request->get("filter", '');
            if(!empty($filter)){
                $filter=(array)json_decode($filter, true);
                if(!empty($filter['sort'])){
                    $sort_id=$filter['sort'];
                }
            }
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('spread')
                ->withJoin(['svip' => ['name'],'level'=>['name']], 'left')
                ->withSearch(['sort'],['sort'=>$sort_id])
                ->where($where)
                ->where('spread.admin_id',$this->admin_uid)
                ->group('spread.id')
                ->order($sort,$order)
                ->count();
            $list = $this->model->alias('spread')
                ->withJoin(['svip' => ['name'],'level'=>['name']], 'left')
                ->withSearch(['sort'],['sort'=>$sort_id])
                ->where('spread.admin_id',$this->admin_uid)
                ->where($where)
                ->group('spread.id')
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
            return json(['status' => 200, 'data' => $list, 'total' => $total]);
        }
        $sort_list = ResourceSort::where(['status' => 1])->field('id,pid,name')->order('indexid asc,id asc')->select()->toArray();
        $tree = new Tree();
        $tree->init($sort_list, 'pid');
        $tree_list = $tree->getTree(0);
        $this->assign('sortSelect', $tree_list);
        $platform=config('version.auth_platform');
        $this->assign('platform', $platform);
        return $this->view->fetch();
    }

    /**
     * 更新单个字段值
     */
    public function setUp($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($this->request->isAjax()) {
            if ($this->request->has('params') && !empty($this->request->post("params"))) {
                $values = json_decode($this->request->post("params"), true);
                $count = 0;
                Db::startTrans();
                try {
                    $pk = $this->model->getPk();
                    #判断最低价格
                    if (isset($values['price'])) {
                        $min_pub = Admin::where('id', $this->admin_uid)->value('min_pub');
                        if ($min_pub > 0) {
                            if ($values['price'] < $min_pub) {
                                return callback(404, '价格不能低于' . $min_pub . '元');
                            }
                        }
                    }
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if ($ids) {
                        $where[] = [$pk, 'in', $ids];
                    }
                    $list = $this->model->where($where)->select();
                    foreach ($list as $item) {
                        $count += $item->allowField(true)->isUpdate(true)->save($values);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '更新失败');
                }
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 编辑资源
     * @return [type] [description]
     */

    public function edit($ids = null)
    {
        $id = input('get.ids/d',0);
        if(!$id) {
            return callback(400, '参数错误');
        }
        $spread = spreadModel::with(['info'=>['free_content','content']])->where('spread.id', $id)->find();
        if(!$spread) {
            return callback(400, '资源不存在');
        }
        $groups = SpreadGroup::alias('a')->where('a.rid', $id)->join('groups b', 'a.gid=b.id')->field('a.*,b.name')->select()->toArray();
        $groups = array_column($groups, null, 'gid');
        $jump = SpreadJump::alias('a')->where('a.rid', $id)->join('jump b', 'a.jid=b.id')->field('a.*,b.name')->select()->toArray();
        $jump = array_column($jump, null, 'jid');
        if (request()->isAjax()) {
            $data = input('post.');
            if (!isset($data['sort_id'])) {
                return callback(400, '请选择资源分类');
            }
            $data['utime'] = time();
            if ($data['type'] == 3) {
                $data['ext_code'] = txtractLink($data['link']);
            }
            $result=$this->model->allowField(true)->save($data, ['id' => $id]);
            if(!$result){
                return callback(400, '保存失败');
            }
            $type = SpreadType::where('rid', $id)->select()->toArray();
            $type = array_column($type, null, 'sid');
            $data['sort_id'] = explode(',', $data['sort_id']);
            foreach ($data['sort_id'] as $key => $value) {
                if (isset($type[$value])) {
                    unset($type[$value]);
                } else {
                    SpreadType::create(['rid' => $id, 'sid' => $value]);
                }
            }
            if (!empty($type)) {
                foreach ($type as $key => $value) {
                    SpreadType::destroy($value['id']);
                }
            }
            //关联社群
            if (!empty($data['gid'])) {
                foreach ($data['gid'] as $key => $value) {
                    if (isset($groups[$value])) {
                        unset($groups[$value]);
                    } else {
                        SpreadGroup::create(['rid' => $id, 'gid' => $value]);
                    }
                }
            }
            if (!empty($groups)) {
                foreach ($groups as $key => $value) {
                    SpreadGroup::destroy($value['id']);
                }
            }
            //关联跳转
            if (!empty($data['jid'])) {
                foreach ($data['jid'] as $key => $value) {
                    if (isset($jump[$value])) {
                        unset($jump[$value]);
                    } else {
                        SpreadJump::create(['rid' => $id, 'jid' => $value]);
                    }
                }
            }
            if (!empty($jump)) {
                foreach ($jump as $key => $value) {
                    SpreadJump::destroy($value['id']);
                }
            }
            //处理关联资源详情
            if(!empty($data['content'])){
                $infos=[
                    'content'=>$data['content'],
                    'free_content'=>$data['free_content'],
                ];
                if (!(new ResourceInfo())->allowField(true)->save($infos,['rid'=> $spread->rid])) {
                    Db::rollback();
                    return callback(400, '详情更新失败');
                }
            }
            return callback(200, '保存成功');
        }
        $this->assign([
            'info' => $spread,
            'groups' => $groups,
            'jump' => $jump
        ]);
        $level = ResourceLevel::where('status', 1)->order('indexid asc')->select();
        $this->assign('levels', $level);
        return $this->fetch();
    }

    //获取分类数据
    public function gettreesort()
    {
        $id = input('ids');
        if ($id) {
            $type = SpreadType::where('rid', $id)->column('sid');
        } else {
            $type = [];
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

    public function groups()
    {
        $list = Groups::where('status', 1)->where('exp_time', '>=', time());
        if ($this->auth->group_id == 2) {
            $list = $list->where('uid', $this->admin_uid);
        }
        $list = $list->select();
        return json(['code' => 0, 'data' => $list, 'count' => count($list)]);
    }

    public function jump()
    {
        $list = Jump::where('status', 1);
        if ($this->auth->group_id == 2) {
            $list = $list->where('uid', $this->admin_uid);
        }
        $list = $list->select();
        return json(['code' => 0, 'data' => $list, 'count' => count($list)]);
    }

    /**
     * 选择社群
     */
    public function selectgroup()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids/a');
            $group_ids = $this->request->param('group_ids/a');
            $where[] = [
                ['admin_id', '=', $this->admin_uid]
            ];
            if (!empty($ids)) {
                $where[] = ['id', 'in', $ids];
            }
            $spread_ids = $this->model->where($where)->column('id');
            if (empty($spread_ids)) {
                return callback(400, '没有可挂载的资源');
            }
            #先执行删除挂载
            $spreadGroup = new SpreadGroup();
            $spreadGroup->where('rid', 'in', $spread_ids)->delete();
            foreach ($spread_ids as $r_id) {
                if (!empty($group_ids)) {
                    foreach ($group_ids as $g_id) {
                        $spreadGroup->create([
                            'rid' => $r_id,
                            'gid' => $g_id,
                            'ctime' => time()
                        ], true);
                    }
                }
            }
            return callback(200, 'success');
        }
        return $this->view->fetch();
    }

    /**
     * 选择挂载
     */
    public function selectjump()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids/a');
            $jump_ids = $this->request->param('jump_ids/a');
            $where[] = [
                ['admin_id', '=', $this->admin_uid],
            ];
            if (!empty($ids)) {
                $where[] = ['id', 'in', $ids];
            }
            $spread_ids = $this->model->where($where)->column('id');
            if (empty($spread_ids)) {
                return callback(400, '没有可挂载的资源');
            }
            #先执行删除挂载
            $spreadJump = new SpreadJump();
            $spreadJump->where('rid', 'in', $spread_ids)->delete();
            foreach ($spread_ids as $r_id) {
                if (!empty($jump_ids)) {
                    foreach ($jump_ids as $j_id) {
                        $spreadJump->create([
                            'rid' => $r_id,
                            'jid' => $j_id,
                            'ctime' => time()
                        ], true);
                    }
                }
            }
            return callback(200, 'success');
        }
        return $this->view->fetch();
    }

    /**
     * 设置资源状态
     * @return [type] [description]
     */
    public function setstatus()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            foreach ($data['data'] as $key => $value) {
                spreadModel::where('id', $value['id'])->update(['status' => $data['status']]);
            }
            return callback(200, '操作成功');
        }
    }

    /**
     * 删除资源
     * @return [type] [description]
     */
    public function del($ids = '')
    {
        if (request()->isAjax()) {
            SpreadType::where('rid', 'in', $ids)->delete();
            $i = (new spreadModel)->destroy($ids);
            if ($i) {
                return callback(200, '删除成功');
            } else {
                return callback(400, '删除失败');
            }
        }
    }
    /**
     * 清空推广资源
     * @return [type] [description]
     */
    public function clearDel()
    {
        if (request()->isAjax()) {
            $result = (new spreadModel)->where('admin_id',  $this->admin_uid)->delete();
            if ($result) {
                return callback(200, '清空成功');
            } else {
                return callback(400, '清空失败');
            }
        }
    }
    /**
     * 生成课程推广海报
     */
    public function coursePoster()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type');
            $id = $this->request->param('id/d', 0);
            if (empty($id)) {
                return callback(404, '资源ID不能为空');
            }
            $info = $this->model->where(['id' => $id, 'status' => 1])->find();
            if (empty($info)) {
                return callback(404, '资源不存在或被禁用');
            }
            $path = 'uploads/poster';
            $filename = 'spread_agent_' . $type . '_' . uniqid() . '.png';
            #获取自定义海报数据
            $setting = [
                'bg_color' => 'rgb(254,167,0)',
                'main_text' => '给您分享了一个好资源，扫码完成任务可免费领取',
                'bg_main_color' => 'rgb(255,255,255)',
                'sub_text' => '免费好资源就等你来',
            ];
            $config = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => 1])->find();
            if (!empty($config)) {
                $setting = json_decode($config->setting, true);
            }
            $setting['bg_color'] = coverToRGB($setting['bg_color']);
            $setting['bg_main_color'] = coverToRGB($setting['bg_main_color']);
            $width = 1080;
            $height = 1600;
            $poster = new PosterMaker($width, $height, $setting['bg_color']);
            $poster->addImg(config('setting.web_logo'), [40, 40], [128, 128], 64);
            $poster->addText(config('setting.web_name'), 30, [220, 80], [255, 255, 255]);
            $poster->addText($setting['main_text'], 22, [220, 140], [255, 255, 255]);
            $poster->addBg(($width - 80), $height * 0.84, [40, $height * 0.13], $setting['bg_main_color'], 24);
            $poster->addImg($info->thumb, [80, ($height * 0.13) + 40], [$width - 160, $width - 240], 0);
            $poster->addText('¥ ' . $info->price, 38, [80, $height * 0.73], [251, 55, 55]);
            $poster->addText($info->title, 34, [80, $height * 0.78], [10, 10, 10], '', 0, 600);
            $poster->addText($setting['sub_text'], 30, [80, $height * 0.88], [100, 100, 100], '', 0, 650);

            $path_url = 'pages/share/jump?fid=' . $this->admin_uid . '&type=2&id=' . $info->id . '&r_type=' . $info->type;
            switch ($type) {
                case 'wechat':
                    list($result, $qrcode) = $this->qrcodeUrl($type, $info->id . '_2_0_' . $this->admin_uid . '_' . $info->type);
                    if (!$result) {
                        return callback(404, $qrcode);
                    }
                    $poster->addImg($qrcode, [($width - 340), $height * 0.78], [256, 256]);
                    break;
                case 'h5':
                    $site_url = (new AdminSite())->getSiteUrl($this->admin_uid);
                    $text_link = $site_url . '/#/' . $path_url;
                    $poster->addQrCode($text_link, [($width - 340), $height * 0.78], [256, 256]);
                    break;
                case 'douyin':
                    list($result, $qrcode) = $this->qrcodeUrl($type, $path_url);
                    if (!$result) {
                        return callback(404, $qrcode);
                    }
                    $poster->addImg($qrcode, [($width - 340), $height * 0.78], [256, 256]);
                    break;
            }
            $poster->addText($info->sales . config('setting.sale_unit_text'), 22, [80, $height * 0.93], [254, 167, 0]);
            $content = $poster->render($filename, 1); // 保持为图片
            $storage_type = config('setting.upload_storage');
            if ($storage_type == 'local') {
                list($res, $info) = $this->savePoster($content, $path, $filename, true);
                if (!$res) {
                    return callback(404, $info);
                }
                return callback(200, 'success', '', $this->request->domain() . '/' . $path . '/' . $info);
            } elseif ($storage_type == 'aliyun') {
                $oss = new Alioss();
                $result = $oss->pudata($filename, $content, $path);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($filename, $content, $path);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $path . '/' . $filename);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            }
        }
    }

    /**
     * 获取课程推广链接
     */
    public function courseUrl()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type');
            $id = $this->request->param('id/d', 0);
            if (empty($id)) {
                return callback(404, '资源ID不能为空');
            }
            $info = $this->model->where(['id' => $id, 'status' => 1])->find();
            if (empty($info)) {
                return callback(404, '资源不存在或被禁用');
            }
            switch ($type) {
                case 'wechat':
                    try {
                        $app = Facade::miniProgram();
                        $accessToken = $app->access_token;
                        $token = $accessToken->getToken();
                        $token = $token['access_token'];
                        $client = new Client();
                        $response = $client->request('POST',
                            'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $token,
                            [
                                'json' => [
                                    'jump_wxa' => [
                                        'path' => '/pages/share/jump',
                                        'query' => 'fid=' . $this->admin_uid . '&type=2&id=' . $info->id . '&r_type=' . $info->type
                                    ],
                                    "is_expire" => false
                                ],
                                'verify' => false
                            ]
                        );
                        $info = $response->getBody()->getContents();
                        $info = json_decode($info, true);
                        if ($info['errcode'] == 0) {
                            return callback(200, 'success', '', $info['openlink']);
                        } else {
                            return callback(404, $info['errmsg']);
                        }
                    } catch (\EasyWeChat\Kernel\Exceptions\Exception $e) {
                        return callback(404, $e->getMessage());
                    } catch (HttpException $e) {
                        return callback(404, $e->getMessage());
                    } catch (Exception $e) {
                        return callback(404, $e->getMessage());
                    }
                    break;
                case 'h5':
                    $site_url = (new AdminSite())->getSiteUrl($this->admin_uid);
                    $text_link = $site_url . '/#/pages/share/jump?fid=' . $this->admin_uid . '&type=2&id=' . $info->id . '&r_type=' . $info->type;
                    return callback(200, 'success', '', $text_link);
                    break;
                case 'douyin':

                    break;
                case 'pc':
                    $text_link = $this->request->domain() . '/f_' . $this->admin_uid;
                    return callback(200, 'success', '', $text_link);
                    break;
            }
        }
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
        $list = \app\common\model\Theme::where('admin_id', $this->admin_uid)->select();
        $user = Admin::where('id', $this->admin_uid)->find();
        $this->view->assign('user', $user);
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 生成推广海报
     */
    public function posterUrl()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type');
            $path = 'uploads/poster';
            $filename = 'spread_' . $this->admin_uid . '_' . uniqid() . '.png';
            #获取自定义海报数据
            $setting = [
                'url' => $this->request->domain() . '/static/common/images/poster_agent.jpg',
                'bom_text' => '现在永远是好的创业时机',
                'bom_text_size' => '16',
                'bom_text_color' => 'rgb(0,0,0)'
            ];
            $config = PosterConfig::where(['admin_id' => $this->admin_uid, 'type' => 3])->find();
            if (!empty($config)) {
                $setting = json_decode($config->setting, true);
            }
            $setting['bom_text_color'] = coverToRGB($setting['bom_text_color']);
            $width = 640;
            $height = 1138;
            $poster = new PosterMaker($width, $height, [255, 255, 255]);
            $poster->addImg($setting['url'], [0, 0], [640, 1138], 0);
            switch ($type) {
                case 'wechat':
                    list($result, $qrcode) = $this->qrcodeUrl($type, '0_1_0_' . $this->admin_uid . '_0');
                    if (!$result) {
                        return callback(404, $qrcode);
                    }
                    $poster->addImg($qrcode, [(($width / 2) - (300 / 2)), $height * 0.67], [300, 300]);
                    break;
                case 'h5':
                    $site_url = (new AdminSite())->getSiteUrl($this->admin_uid);
                    $text_link = $site_url . '/#/pages/share/jump?fid=' . $this->admin_uid;
                    $poster->addQrCode($text_link, [(($width / 2) - (300 / 2)), $height * 0.67], [300, 300]);
                    break;
                case 'douyin':
                    list($result, $qrcode) = $this->qrcodeUrl($type, 'pages/share/jump?fid=' . $this->admin_uid);
                    if (!$result) {
                        return callback(404, $qrcode);
                    }
                    $poster->addImg($qrcode, [(($width / 2) - (300 / 2)), $height * 0.67], [300, 300]);
                    break;
            }
            $poster->addText($setting['bom_text'], $setting['bom_text_size'] * 1.5, [0, $height * 0.98], $setting['bom_text_color']);
            $content = $poster->render($filename, 1); // 保持为图片
            $storage_type = config('setting.upload_storage');
            if ($storage_type == 'local') {
                list($res, $info) = $this->savePoster($content, $path, $filename, true);
                if (!$res) {
                    return callback(404, $info);
                }
                return callback(200, 'success', '', $this->request->domain() . '/' . $path . '/' . $info);
            } elseif ($storage_type == 'aliyun') {
                $oss = new Alioss();
                $result = $oss->pudata($filename, $content, $path);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($filename, $content, $path);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $path . '/' . $filename);
                if ($result['status'] !== 200) {
                    return callback(404, $result['msg']);
                }
                return callback(200, 'success', '', $result['data']['url']);
            }
        }
    }

    /**
     * 获取二维码地址
     */
    protected function qrcodeurl($type, $scene = '')
    {
        switch ($type) {
            case 'wechat':
                try {
                    $app = Facade::miniProgram();
                    $response = $app->app_code->getUnlimit($scene, ['page' => 'pages/share/jump']);
                    if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                        $storage_type = config('setting.upload_storage');
                        $path = 'uploads/agent/qrcode';
                        if ($storage_type == 'local') {
                            $filename = $response->saveAs($path, 'qrcode_' . uniqid(), true);
                            if (!$filename) {
                                return [false, '生成失败'];
                            }
                            return [true, $this->request->domain() . '/' . $path . '/' . $filename];

                        } elseif ($storage_type == 'aliyun') {
                            $contents = $response->saveContent($path, 'qrcode_' . uniqid(), true);
                            $filename = uniqid() . '.jpg';
                            $oss = new Alioss();
                            $result = $oss->pudata($filename, $contents, $path);
                            if ($result['status'] !== 200) {
                                return [false, $result['msg']];
                            }
                            return [true, $result['data']['url']];

                        } elseif ($storage_type == 'qcloud') {
                            $contents = $response->saveContent($path, 'qrcode_' . uniqid(), true);
                            $filename = uniqid() . '.jpg';
                            $oss = new Qcloud();
                            $result = $oss->pudata($filename, $contents, $path);
                            if ($result['status'] !== 200) {
                                return [false, $result['msg']];
                            }
                            return [true, $result['data']['url']];
                        } elseif ($storage_type == 'qiniu') {
                            $qiniu = new Qiniu();
                            $filename = uniqid() . '.jpg';
                            $contents = $response->saveContent($path, 'qrcode_' . uniqid(), true);
                            $result = $qiniu->uploadData($contents, $path . $filename);
                            if ($result['status'] !== 200) {
                                return [false, '生成失败'];
                            }
                            return [true, $result['data']['url']];
                        }
                    }
                } catch (\EasyWeChat\Kernel\Exceptions\Exception $e) {
                    return [false, $e->getMessage()];
                } catch (HttpException $e) {
                    return [false, $e->getMessage()];
                } catch (Exception $e) {
                    return [false, $e->getMessage()];
                }
                break;
            case 'douyin':
                try {
                    $toutiao = new Toutiao();
                    list($result, $contents) = $toutiao->getQrcode(['path' => urlencode($scene)]);
                    if (!$result) {
                        return callback(404, $contents);
                    }
                    $storage_type = config('setting.upload_storage');
                    $path = 'uploads/agent/qrcode';
                    $filename = 'agent_douyin_qrcode_' . uniqid() . '.png';
                    if ($storage_type == 'local') {
                        list($res, $info) = $this->savePoster($contents, $path, $filename, true);
                        if (!$res) {
                            return callback(404, $info);
                        }
                        return callback(200, 'success', '', $this->request->domain() . '/' . $path . '/' . $info);

                    } elseif ($storage_type == 'aliyun') {
                        $oss = new Alioss();
                        $result = $oss->pudata($filename, $contents, $path);
                        if ($result['status'] !== 200) {
                            return [false, $result['msg']];
                        }
                        return [true, $result['data']['url']];

                    } elseif ($storage_type == 'qcloud') {
                        $oss = new Qcloud();
                        $result = $oss->pudata($filename, $contents, $path);
                        if ($result['status'] !== 200) {
                            return [false, $result['msg']];
                        }
                        return [true, $result['data']['url']];
                    } elseif ($storage_type == 'qiniu') {
                        $qiniu = new Qiniu();
                        $result = $qiniu->uploadData($contents, $path . $filename);
                        if ($result['status'] !== 200) {
                            return [false, '生成失败'];
                        }
                        return [true, $result['data']['url']];
                    }
                } catch (Exception $e) {
                    return [false, $e->getMessage()];
                }
                break;

        }
    }

    /**
     * 保存本地图片
     */
    protected function savePoster($contents, $directory, $filename = '', $appendSuffix = true)
    {
        $directory = rtrim($directory, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // @codeCoverageIgnore
        }
        if (!is_writable($directory)) {
            return [false, sprintf("'%s' 目录写入失败.", $directory)];
        }
        if (empty($contents) || '{' === $contents[0]) {
            return [false, 'Invalid media response content.'];
        }
        if ($appendSuffix && empty(pathinfo($filename, PATHINFO_EXTENSION))) {

            $filename .= File::getStreamExt($contents);

        }
        file_put_contents($directory . '/' . $filename, $contents);
        return [true, $filename];
    }

    /**
     * 获取推广链接
     */
    public function shortUrl()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type');
            switch ($type) {
                case 'wechat':
                    try {
                        $app = Facade::miniProgram();
                        $accessToken = $app->access_token;
                        $token = $accessToken->getToken();
                        $token = $token['access_token'];
                        $client = new Client();
                        $response = $client->request('POST',
                            'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $token,
                            [
                                'json' => [
                                    'jump_wxa' => [
                                        'path' => '/pages/share/jump',
                                        'query' => 'fid=' . $this->admin_uid
                                    ],
                                    "is_expire" => false
                                ],
                                'verify' => false
                            ]
                        );
                        $info = $response->getBody()->getContents();
                        $info = json_decode($info, true);
                        if ($info['errcode'] == 0) {
                            return callback(200, 'success', '', $info['openlink']);
                        } else {
                            return callback(404, $info['errmsg']);
                        }
                    } catch (\EasyWeChat\Kernel\Exceptions\Exception $e) {
                        return callback(404, $e->getMessage());
                    } catch (HttpException $e) {
                        return callback(404, $e->getMessage());
                    } catch (Exception $e) {
                        return callback(404, $e->getMessage());
                    }
                    break;
                case 'h5':
                    $site_url = (new AdminSite())->getSiteUrl($this->admin_uid);
                    $text_link = $site_url . '/#/pages/share/jump?fid=' . $this->admin_uid;
                    return callback(200, 'success', '', $text_link);
                    break;
                case 'douyin':

                    break;
                case 'pc':
                    $text_link = $this->request->domain() . '/f_' . $this->admin_uid;
                    return callback(200, 'success', '', $text_link);
                    break;
            }
        }
    }

    /**
     * 小程序搜索推广
     * @return [type] [description]
     */
    public function submitpages()
    {
        $app = Facade::miniProgram();
        $accessToken = $app->access_token;
        $token = $accessToken->getToken();
        $token = $token['access_token'];
        $pages[] = [
            'path' => 'pages/tabbar/home/index',
            'query' => '',
        ];
        $pages[] = [
            'path' => 'pages/tabbar/sort/index',
            'query' => '',
        ];
        $pages[] = [
            'path' => 'pages/resource/index',
            'query' => 'type=1',
        ];
        $pages[] = [
            'path' => 'pages/resource/index',
            'query' => 'type=2',
        ];
        $list = spreadModel::field('id,type')->where('status', 1)->select();
        foreach ($list as $v) {
            if ($v['type'] == 1) {
                $path = 'pages/resource/resource';
            } elseif ($v['type'] == 2) {
                $path = 'pages/resource/video';
            } elseif ($v['type'] == 3) {
                $path = 'pages/resource/normal';
            } elseif ($v['type'] == 4) {
                $path = 'pages/resource/coursevideo';
            } elseif ($v['type'] == 5) {
                $path = 'pages/resource/courseaudio';
            } elseif ($v['type'] == 6) {
                $path = 'pages/resource/cdkey';
            }
            $pages[] = [
                'path' => $path,
                'query' => 'id=' . $v['id'],
            ];
        }
        $client = new Client();
        $response = $client->request('POST',
            'https://api.weixin.qq.com/wxa/search/wxaapi_submitpages?access_token=' . $token,
            [
                'json' => [
                    'pages' => $pages
                ],
                'verify' => false
            ]
        );
        $info = $response->getBody()->getContents();
        $info = json_decode($info, true);
        if ($info['errcode'] == 0) {
            return callback(200, '提交成功');
        } else {
            return callback(404, $info['errmsg']);
        }

    }

    /**
     * 批量修改价格
     */
    public function batchMoney()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (!isset($params['min_money'])) {
                        return callback(404, '请输入最小随机价格');
                    }
                    if (!isset($params['max_money'])) {
                        return callback(404, '请输入最大随机价格');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大随机价格必须大于最小价格');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    #不能低于最低金额
                    if ($params['type'] == 1) {
                        $min_pub = Admin::where('id', $this->admin_uid)->value('min_pub');
                        if ($min_pub > 0) {
                            if ($params['min_money'] < $min_pub) {
                                return callback(404, '最小价格不能低于' . $min_pub . '元');
                            }
                        }
                    } else {
                        $dis_pub = Admin::where('id', $this->admin_uid)->value('dis_pub');
                        if ($dis_pub > 0) {
                            if ($params['min_money'] < $dis_pub) {
                                return callback(404, '最小价格不能低于' . $dis_pub . '元');
                            }
                        }
                    }

                    if ($params['type'] == 1) {#现价
                        $result = $this->model->where($where)->update([
                            'price' => $params['min_money'] == 0 ? 0 : Db::raw('round(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ',1)')
                        ]);
                    } else {#原价
                        $result = $this->model->where($where)->update([
                            'dis_price' => $params['min_money'] == 0 ? 0 : Db::raw('round(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ',1)')
                        ]);
                    }
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        //获取代理最低发布金额
        $type = $this->request->param("type/d", 1);
        $agent = Admin::where('id', $this->admin_uid)->field('min_pub,dis_pub')->find();
        if ($type == 1) {
            $min_pub = $agent->min_pub > 0 ? $agent->min_pub : 1;
            $max_pub = $agent->min_pub > 0 ? $agent->min_pub : 1;
        } else {
            $min_pub = $agent->dis_pub > 0 ? $agent->dis_pub : 10;
            $max_pub = $agent->dis_pub > 0 ? $agent->dis_pub : 10;
        }
        $this->view->assign('min_pub', $min_pub);
        $this->view->assign('max_pub', $max_pub);
        return $this->view->fetch('money');
    }

    /**
     * 批量修改销量
     */
    public function batchSales()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['min_money'])) {
                        return callback(404, '请输入最小随机量');
                    }
                    if (empty($params['max_money'])) {
                        return callback(404, '请输入最大随机量');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大随机量必须大于最小随机量');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'sales' => Db::raw('floor(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ')')
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch('sales');
    }

    /**
     * 批量修改邀请人数
     */
    public function batchInvites()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['min_money'])) {
                        return callback(404, '请输入最小邀请人数');
                    }
                    if (empty($params['max_money'])) {
                        return callback(404, '请输入最大邀请人数');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大人数必须大于最小人数');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'invite_num' => Db::raw('floor(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ')')
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch('invites');
    }

    /**
     * 批量试看时间
     */
    public function batchTrysee()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['min_money'])) {
                        return callback(404, '请输入最小试看时间');
                    }
                    if (empty($params['max_money'])) {
                        return callback(404, '请输入最大试看时间');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大试看时间必须大于最小试看时间');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'try_see' => Db::raw('floor(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ')')
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch('trysee');
    }

    /**
     * 批量资源等级
     */
    public function batchLevel()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['level'])) {
                        return callback(404, '请选择资源等级');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'level' => $params['level']
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $levels = ResourceLevel::where('status', 1)->order('indexid asc,id asc')->select();
        $this->view->assign('levels', $levels);
        return $this->view->fetch('levels');
    }

    /**
     * 批量会员专享
     */
    public function batchSvip()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['svip_id'])) {
                        $params['svip_id']=0;
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'is_vip' => $params['svip_id']
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $sviplist = \app\common\model\Svip::where(['status'=> 1,'type'=>0,'admin_id'=>$this->admin_uid])->order('level asc,id asc')->select();
        $this->view->assign('sviplist', $sviplist);
        return $this->view->fetch('svips');
    }

    /**
     * 批量设置分销分配方式
     */
    public function batchRetail()
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
                    $where[] = ['admin_id', '=', $this->admin_uid];
                    if (empty($params['sell'])) {
                        return callback(404, '请输入佣金');
                    }
                    if (!empty($params['ids'])) {
                        $params['ids'] = explode(',', $params['ids']);
                        $where[] = ['id', 'in', $params['ids']];
                    }
                    $result = $this->model->where($where)->update([
                        'sell_set' => $params['sell_set'],
                        'sell_type' => $params['sell_type'],
                        'sell' => $params['sell'],
                        'sell_type2' => empty($params['sell_type2']) ? 1 : $params['sell_type2'],
                        'sell2' => empty($params['sell2']) ? '0.05' : $params['sell2']
                    ]);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch('retail');
    }

}