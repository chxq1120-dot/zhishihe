<?php
// +----------------------------------------------------------------------
// | ZHIPALLWCCE [ Wisdom Create Cloud Common ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2021 http://www.zhipall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: workrd <304609001@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\common\model\AdminBill;
use app\common\model\AudioCourse;
use app\common\model\Invite;
use app\common\model\Privilege;
use app\common\model\SpreadHand;
use app\common\model\{Resource as ResourceModel,
    ResourceSort,
    ResourceType,
    User,
    UserColl,
    ResourceTask,
    UserResource
};
use app\api\validate\Check;
use app\common\model\ResourceKammi;
use app\common\model\ResourceLevel;
use app\common\model\Spread;
use app\common\model\SpreadGroup;
use app\common\model\SpreadJump;
use app\common\model\SpreadType;
use app\common\model\SpreadView;
use app\common\model\SvipPrivilege;
use app\common\model\UserSubscribe;
use app\common\model\UserThird;
use app\common\model\VideoCourse;
use app\common\model\ViewLog;
use Naixiaoxin\ThinkWechat\Facade;
use think\Exception;
use zp\Tree;

class Resource extends Common
{

    public function initialize()
    {
        $this->model = new Spread();
    }
    /**
     * 资源分类
     * @return [type] [description]
     */
    public function resourcesort()
    {
        $sort_id = request()->param('sort_id/d', 0);
        $list = (new ResourceSort)
            ->where(['status' => 1, 'pid' => $sort_id])->order('indexid asc,id asc');
        $data = request()->param();
        if (isset($data['limit'])) {
            $list->limit($data['limit']);
        }
        $list = $list->select()->toArray();
        if (empty($list)) {
            $this->error('没有数据');
        }
        $this->success('success', ['list' => $list]);
    }
    /**
     * 查询全部资源分类
     * @return [type] [description]
     */
    public function allsort()
    {
        $sort_list = [];
        $list = (new ResourceSort)->where('status', 1)->field('id,pid,thumb,name')->order('indexid asc,id asc')->select();
        if ($list) {
            $sort_list = $list->toArray();
        }
        $tree = new Tree();
        $tree->init($sort_list, 'pid', '', 'children');
        $sort_list = $tree->getTreeArray('pid');
        $level_list = ResourceLevel::where('status', 1)->order('indexid asc')->select();
        array_unshift($sort_list, ['id' => 0, 'name' => '不限分类']);
        $this->success('success', ['list' => $sort_list, 'level_list' => $level_list]);
    }

    /**
     * 资源列表
     * @return [type] [description]
     */
    public function resource()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.resource')->check($data)) {
                $this->error($validate->getError());
            }
            $field = 'a.id,a.title,a.thumb,a.type,a.price,a.dis_price,a.desc,a.sales,a.level,a.utime,a.ctime,a.is_vip,s.name as svip_name';
            $start = ($data['page'] - 1) * $data['limit'];
            $where[] = ['a.admin_id', '=', $this->request->from_id];
            $where[] = ['a.status', '=', 1];
            $list = $this->model->alias('a')->field($field);
            $sort_name = '';
            if (!empty($data['sort_id'])) { //指定分类资源
                $sortModel = new ResourceSort();
                $sort_arr = $sortModel->where('status', 1)->order('indexid asc')->select();
                if ($sort_arr) {
                    $sort_arr = $sort_arr->toArray();
                    $tree = new Tree();
                    $tree->init($sort_arr, 'pid');
                    $child_ids = $tree->getChildrenIds($data['sort_id'], true);
                    $list->join('spread_type b', 'a.id=b.rid');
                    $where[] = ['b.sid', 'in', $child_ids];
                    $sort_name = $sortModel->where('id', $data['sort_id'])->value('name');
                }
            }
            #关联VIP表
            $list = $list->leftJoin('svip s','s.id=a.is_vip');

            if (!empty($data['level'])) {//资源等级
                $where[] = ['a.level', 'eq', $data['level']];
            }
            if (!empty($data['keys'])) {//搜索
                $where[] = ['a.title|a.desc', 'like', '%' . $data['keys'] . '%'];
            }
            $order = 'a.id desc';
            $list->where($where);
            if (isset($data['type'])) {//排序
                if ($data['type'] == 1) {//推荐
                    $list->where('a.is_top', 1);
                    $order = 'a.is_top desc,a.ctime desc';
                }
            }
            if (isset($data['order'])) {//排序
                if ($data['order'] == 0) {//默认
                    $order = 'a.utime desc,a.id desc';
                } elseif ($data['order'] == 1) {//最新
                    $order = 'a.utime desc,a.ctime desc';
                } elseif ($data['order'] == 2) {//价格低到高
                    $order = 'a.price asc,a.id desc';
                } elseif ($data['order'] == 3) {//价格从高到底
                    $order = 'a.price desc,a.id desc';
                } elseif ($data['order'] == 4) {//销量从高到底
                    $order = 'a.sales desc,a.id desc';
                }
            }
            $list = $list->limit($start, $data['limit'])->order($order)->group('a.id')->select();
            foreach ($list as $key => $value) {
                if($value['is_vip']==0){
                    $list[$key]['svip_name'] ='会员专享';
                }elseif($value['is_vip']==-1){
                    $list[$key]['svip_name'] ='';
                }
                $list[$key]['level_name'] = (new ResourceLevel())->getLevelName($value['level']);
                $list[$key]['desc'] = mb_substr($value['desc'], 0, 32) . '...';
                $list[$key]['update'] = formatTime(strtotime($value['ctime']));
                if (!empty($value['utime'])) {
                    $list[$key]['update'] = formatTime($value['utime']);
                }
            }
            $this->success('success', ['list' => $list, 'sort_name' => $sort_name]);
        }
    }

    /**
     * 资源详情
     * @return [type] [description]
     */
    public function resdetail()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.resdetail')->check($data)) {
                $this->error($validate->getError());
            }
            $field = 'a.id,a.thumb,a.title,a.level,a.link,a.ext_code,a.type,a.price,a.dis_price,a.desc,b.free_content,b.content,a.sales,a.invite,a.invite_num,a.exc_video,a.try_see,a.rid,a.is_vip';
            $info = $this->model->alias('a')->join('resource_info b','a.rid=b.rid')->where(['a.id'=>$data['id'],'a.status'=>1])
                ->field($field)->find();
            if (!$info) {
                $this->error('资源获取失败');
            }
            $info->views = ['inc', 1];
            $info->save();
            $info->level_name = (new ResourceLevel())->getLevelName($info->level);
            #查询课程视频列表
            $info->video_list = [];
            $info->video_num = 0;
            if ($info->type == 4) {
                $info->video_list = VideoCourse::where('rid', $info->rid)->order('indexid asc,id asc')->select();
                $info->video_num = count($info->video_list);
            }
            #查询课程音频列表
            $info->audio_list = [];
            $info->audio_num = 0;
            if ($info->type == 5) {
                $info->audio_list = AudioCourse::where('rid', $info->rid)->order('indexid asc,id asc')->select();
                $info->audio_num = count($info->audio_list);
            }
            $info->svip_name=\app\common\model\Svip::getResSvip($info->is_vip);
            #用户查看权限
            $info->is_auth = 0;
            $info->percent = 0;
            $info->is_subscribe = 0;
            $info->re_price = 0;#折扣价
            $uid = 0;
            if (!empty($data['token'])) {//已登录获取收藏和任务数据
                $user = User::where(['token' => $data['token'], 'status' => 1])->find();
                if ($user) {
                    $uid = $user->id;
                    $iscoll = UserColl::where('uid', $user->id)->where('rid', $info->id)->find();
                    if ($iscoll) {//是否收藏
                        $info->is_coll = 1;
                    } else {
                        $info->is_coll = 0;
                    }
                    #todo 判断是否有会员权限
                    if ($user->vid > 0 && $user->exp_time > time()) {
                        $is_level = 0;
                        #等级
                        $level = Privilege::handleUserAuth($user->vid, $info->level, 1);
                        if ($level) {
                            $is_level = $level;
                        }
                        #数量
                        $is_limits = 0;
                        $today_num = UserResource::where(['uid' => $user->id, 'type' => 3])->whereTime('ctime', 'today')->count();
                        $limits = Privilege::handleUserAuth($user->vid, $today_num, 2);
                        if ($limits) {
                            $is_limits = $limits;
                        }
                        #分类
                        $is_sorts = 0;
                        $sort_ids = SpreadType::where('rid', $info->id)->column('sid');
                        $sorts = Privilege::handleUserAuth($user->vid, $sort_ids, 3);
                        if ($sorts) {
                            $is_sorts = $sorts;
                        }
                        #判断
                        if ($is_level == 2 || $is_limits == 2 || $is_sorts == 2) {
                            $info->is_auth = 1;
                        }
                        if ($info->is_auth == 1) {
                            if ($is_level == 1 || $is_limits == 1 || $is_sorts == 1) {
                                $info->is_auth = 0;
                            }
                        }
                        #判断是否设置了会员折扣价
                        $dis_price = \app\common\model\Svip::getDiscountPrice($user->vid, $info->price);
                        if ($info->is_auth == 1 && $dis_price > 0) {
                            $info->re_price = $dis_price;
                            $info->is_auth = 0;
                        }
                        #判断会员专享资源
                        if($info->is_vip>0 && $user->vid<$info->is_vip){
                            $info->is_auth = 0;
                        }
                        #写入会员获取资源记录
                        if ($info->is_auth == 1 && $dis_price == 0) {
                            $this->setUserRes($uid, $info->id, $user->admin_id);
                        }
                    }
                    #todo 判断是否购买了资源或完成了任务
                    $auth = UserResource::where('rid', $data['id'])->where('uid', $user->id)->find();
                    if ($auth) {
                        if ($info->is_auth == 0 && $auth->type == 3) {
                            $info->is_auth = 0;
                        }else{
                            $info->is_auth = 1;
                        }
                    } else {
                        #没有查询到记录
                        if (empty(floatval($info->price))) {
                            if (empty($info->invite) && empty($info->exc_video)) {
                                $info->is_auth = 1;
                            }
                            #非微信小程序只有邀请任务
                            if (empty($info->invite) && !is_miniWechat($this->plat_form)) {
                                $info->is_auth = 1;
                            }
                        }
                    }
                    #todo 读取是否订阅了任务
                    $template_id = config('setting.subscribe_task_id');
                    $subscribe = UserSubscribe::where(['uid' => $uid, 'temp_id' => $template_id, 'rid' => $info->id, 'result' => 'accept'])->find();
                    if (!empty($subscribe)) {
                        $info->is_subscribe = 1;
                    }
                }
            }
            #todo 判断是否显示网盘链接
            if ($info->is_auth !== 1 && $info->type == 1) {
                $info->link = '';
                $info->ext_code = '';
            }
            #todo 判断展示文档下载链接
            if ($info->is_auth == 1 && $info->type == 3 && !empty($info->ext_code) && (!is_miniWechat($this->plat_form) && isMobile())){
                $info->link = $this->request->domain().'/api/resource/download?token='.$data['token'].'&id='.$info->id;
            }
            #记录浏览记录
            $ip = $this->request->ip();
            $this->setLog($uid, $ip, $data['id'], $this->request->from_id);
            #查询关联社群
            $groups = SpreadGroup::withJoin(['groups'])
                ->where(['rid' => $info->id, 'status' => 1])
                ->whereTime('exp_time', '>', time())
                ->order('spread_group.ctime desc')
                ->select();
            $info->group = $groups;
            #关联跳转
            $jumps = SpreadJump::withJoin(['jumps'])
                ->where(['rid' => $info->id, 'status' => 1])
                ->order('spread_jump.ctime desc')
                ->select();
            $info->jump = $jumps;
            $this->success('success', $info);
        }
    }
    /**
     *下载文档
     * @return void
     */
    public function download()
    {
        try{
            $data = input('get.');
            $validate = new Check;
            if (!$validate->scene('Resource.download')->check($data)) {
                $this->error($validate->getError());
            }
            $spread=Spread::where(['id'=>$data['id'],'status'=>1])->find();
            if(empty($spread)){
                $this->error('文档不存在');
            }
            if($spread->type!==3){
                $this->error('没有文档可以下载1');
            }
            if(empty($spread->link) || empty($spread->ext_code)){
                $this->error('没有文档可以下载2');
            }
            $is_auth=0;
            if (!empty($data['token'])) {//已登录获取收藏和任务数据
                $user = User::where(['token' => $data['token'], 'status' => 1])->find();
                if ($user) {
                    $uid = $user->id;
                    #todo 判断是否有会员权限
                    if ($user->vid > 0 && $user->exp_time > time()) {
                        $is_level = 0;
                        #等级
                        $level = Privilege::handleUserAuth($user->vid, $spread->level, 1);
                        if ($level) {
                            $is_level = $level;
                        }
                        #数量
                        $is_limits = 0;
                        $today_num = UserResource::where(['uid' => $user->id, 'type' => 3])->whereTime('ctime', 'today')->count();
                        $limits = Privilege::handleUserAuth($user->vid, $today_num, 2);
                        if ($limits) {
                            $is_limits = $limits;
                        }
                        #分类
                        $is_sorts = 0;
                        $sort_ids = SpreadType::where('rid', $spread->id)->column('sid');
                        $sorts = Privilege::handleUserAuth($user->vid, $sort_ids, 3);
                        if ($sorts) {
                            $is_sorts = $sorts;
                        }
                        #判断
                        if ($is_level == 2 || $is_limits == 2 || $is_sorts == 2) {
                            $is_auth = 1;
                        }
                        if ($is_auth == 1) {
                            if ($is_level == 1 || $is_limits == 1 || $is_sorts == 1) {
                                $is_auth = 0;
                            }
                        }
                        #判断会员专享资源
                        if($spread->is_vip>0 && $user->vid<$spread->is_vip){
                            $is_auth = 0;
                        }
                    }
                    #todo 判断是否购买了资源或完成了任务
                    $auth = UserResource::where('rid',$spread->id)->where('uid', $user->id)->find();
                    if ($auth) {
                        if ($is_auth == 0 && $auth->type == 3) {
                            $is_auth = 0;
                        }else{
                            $is_auth = 1;
                        }
                    } else {
                        #没有查询到记录
                        if (empty(floatval($spread->price))) {
                            if (empty($spread->invite) && empty($spread->exc_video)) {
                                $is_auth = 1;
                            }
                            #非微信小程序只有邀请任务
                            if (empty($info->invite) && !is_miniWechat($this->plat_form)) {
                                $is_auth = 1;
                            }
                        }
                    }
                }
            }
            if($is_auth==1){
                //处理不同文件输出类型
                $header='';
                switch (strtoupper($spread->ext_code)){
                    case 'DOC':
                        $header='Content-Type: application/msword';
                        break;
                    case 'DOCX':
                        $header='Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        break;
                    case 'PDF':
                        $header='Content-Type: application/pdf';
                        break;
                    case 'PPT':
                        $header='Content-Type: application/vnd.ms-powerpoint';
                        break;
                    case 'PPTX':
                        $header='Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation';
                        break;
                    case 'XLS':
                        $header='Content-Type: application/vnd.ms-excel';
                        break;
                    case 'XLSX':
                        $header='Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                        break;
                }
                if(empty($header)){
                    $this->error('此文件类型不允许下载');
                }
                $filename=urlencode($spread->title).'.'.$spread->ext_code;
                header($header);
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                header('Cache-Control: max-age=0');
                readfile($spread->link);
            }else{
                $this->error('没有权限下载');
            }
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
    }
    /**
     * 记录会员获取资源
     */
    protected function setUserRes($uid, $rid, $admin_id)
    {
        $userResModel = new UserResource();
        $result = $userResModel->where(['uid' => $uid, 'rid' => $rid])->find();
        if (empty($result)) {
            $data = [
                'admin_id' => $admin_id,
                'rid' => $rid,
                'uid' => $uid,
                'type' => 3,
                'ctime' => time(),
            ];
            $res = $userResModel::create($data, true);
            if (!empty($res)) {
                $user = User::where('id', $uid)->find();
                $user->r_nums = ['inc', 1];
                $user->save();
            }
        }
    }

    /**
     * 获取已购买卡密列表
     */
    public function buykmList()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.buykmList')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = $this->model->where('id', $data['id'])->where('status', 1)->find();
            if (!$info) {
                $this->error('资源已下线');
            }
            #todo 读取卡密
            $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => $uid])->order('id desc')->select();
            $this->success('success', ['cdklist' => $kammi]);
        }
    }

    /**
     * 卡密任务回调
     */
    public function kammiTask()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.kammiTask')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = $this->model->where('id', $data['id'])->where('status', 1)->find();
            if (!$info) {
                $this->error('资源已下线');
            }
            #todo 读取卡密
            #todo 判断会员权限
            $user = User::where('id', $uid)->find();
            if ($user->vid > 0 && $user->exp_time > time()) {
                $is_auth = 0;
                #等级
                $level = Privilege::handleUserAuth($user->vid, $info->level, 1);
                if ($level == 2) {
                    $is_auth = 1;
                }
                #数量
                $today_num = UserResource::where(['uid' => $user->id, 'type' => 3])->whereTime('ctime', 'today')->count();
                $limits = Privilege::handleUserAuth($user->vid, $today_num, 2);
                if ($limits == 2) {
                    $is_auth = 1;
                }
                #分类
                $sort_ids = SpreadType::where('rid', $info->id)->column('sid');
                $sorts = Privilege::handleUserAuth($user->vid, $sort_ids, 3);
                if ($sorts == 2) {
                    $is_auth = 1;
                }
                if ($is_auth == 1) {
                    $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => $uid])->order('id desc')->find();
                    if (empty($kammi)) {
                        $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => 0])->order('id asc')->find();
                        if (empty($kammi)) {
                            $this->error('卡密库存不足，请联系管理员处理');
                        }
                        $kammi->uid = $uid;
                        $kammi->status = 1;
                        $kammi->utime = time();
                        $kammi->save();
                    }
                    $this->success('success', ['cdkey' => $kammi->cdkey]);
                }
                $this->error('没有卡密权限，请联系管理员处理');
            } else {
                #单个资源获取
                if ($info->price == 0 && $info->invite == 0 && $info->exc_video == 0) {
                    $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => $uid])->order('id desc')->find();
                    if (empty($kammi)) {
                        $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => 0])->order('id asc')->find();
                        if (empty($kammi)) {
                            $this->error('卡密库存不足，请联系管理员处理');
                        }
                        $kammi->uid = $uid;
                        $kammi->status = 1;
                        $kammi->utime = time();
                        $kammi->save();
                    }
                    $this->success('success', ['cdkey' => $kammi->cdkey]);
                } else {
                    $useRes = UserResource::where(['rid' => $info->id, 'uid' => $uid])->order('id desc')->find();
                    if (!$useRes) {
                        $this->error('卡密获取失败，请联系管理员处理2');
                    }
                    $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => $uid])->order('status asc,id desc')->find();
                    if (empty($kammi)) {
                        $kammi = ResourceKammi::where(['rid' => $info->rid, 'uid' => 0])->order('id asc')->find();
                        if (empty($kammi)) {
                            $this->error('卡密库存不足，请联系管理员处理');
                        }
                        $kammi->uid = $uid;
                        $kammi->status = 1;
                        $kammi->utime = time();
                        $kammi->save();
                    }else{
                        if ($kammi->status == 0) {
                            $kammi->status = 1;
                            $kammi->utime = time();
                            $kammi->save();
                        }
                    }
                    $this->success('success', ['cdkey' => $kammi->cdkey]);
                }
            }

        }
    }

    /**
     * 领取资源任务
     */
    public function receiveTask()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.receivetask')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = $this->model->where('id', $data['id'])->where('status', 1)->find();
            if (!$info) {
                $this->error('资源已下线');
            }
            #每日资源获取判断
            $hand_day_num = intval(config('setting.hand_day_num'));
            if (!empty($hand_day_num)) {
                $task_num = ResourceTask::where('uid', $uid)->whereTime('ctime', 'today')->count();
                if ($task_num >= $hand_day_num) {
                    $this->error('今日获取次数超过限制，请明日再来');
                }
            }
            $task = ResourceTask::where('uid', $uid)->where('rid', $info->id)->find();
            if (!$task) {
                $data = [
                    'admin_id' => $this->request->param('from_id'),
                    'rid' => $info->id,
                    'uid' => $uid,
                    'ctime' => time(),
                    'invite_num' => 0,
                    'is_video' => 0,
                ];
                $task = ResourceTask::create($data, true);
            }
            $task->percent = 0;
            $task->invited = $info->invite_num; #需要邀请的数量
            if (!empty($task->invite_num) && !empty($info->invite_num)) {
                $task->percent = round(($task->invite_num / $info->invite_num) * 100);
            }
            $this->success('success', $task);
        }
    }

    /**
     * 观看完激励视频后回调
     */
    public function subTaskVideo()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.subtaskvideo')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = $this->model->where('id', $data['rid'])->where('status', 1)->find();
            if (!$info) {
                $this->error('资源已下线');
            }
            $task = ResourceTask::where('uid', $uid)->where('rid', $info->id)->find();
            if (!$task) {
                $this->error('没有任务记录');
            }
            $task->is_video = 1;
            $task->utime = time();
            #判断总任务是否完成
            if ($task->invite_num >= $info->invite_num || $info->invite == 0) {
                $task->status = 1;
            }
            $res = $task->save();
            if (!$res) {
                $this->error('更新失败');
            }
            //订阅提醒
            $template_id = config('setting.subscribe_task_id');
            $i = UserSubscribe::where(['temp_id' => $template_id, 'rid' => $info->id, 'uid' => $uid])->where('result', 'accept')->find();
            if ($i) {
                $openid = (new UserThird())->getFieldVal(1, $uid, 'openid');
                $app = Facade::miniProgram();
                if ($task->invite_num >= $info->invite_num || $info->invite == 0) {
                    $thing4 = '任务完成,邀请:' . $task->invite_num . '/' . $info->invite_num;
                    $task_result = '任务完成';
                } else {
                    $thing4 = '任务进度,邀请:' . $task->invite_num . '/' . $info->invite_num;
                    $task_result = '视频完成';
                }
                if ($info->exc_video) {
                    $thing4 .= ',视频:1/1';
                }
                $arr = [
                    'template_id' => $template_id,
                    'touser' => $openid,
                    'page' => '/pages/share/jump?fid=' . $task->admin_id . '&uid=' . $uid . '&type=2&id=' . $info->id . '&r_type=' . $info->type,
                    'data' => [
                        'thing3' => ['value' => '任务:' . mb_substr($info->title, 0, 11) . '...'],
                        'thing4' => ['value' => $thing4],
                        'phrase12' => ['value' => $task_result],
                        'time14' => ['value' => date('Y.m.d H:i')]
                    ]
                ];
                $app->subscribe_message->send($arr);
            }
            $this->success('success');
        }
    }

    /**
     * 完成任务获取资源
     * @return [type] [description]
     */
    public function subrestask()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Resource.subrestask')->check($data)) {
                $this->error($validate->getError());
            }
            if (!empty($data['token'])) {
                $userinfo = User::where(['token' => $data['token'], 'status' => 1])->find();
                if (empty($userinfo)) {
                    $this->error('用户未登录或账号异常');
                }
                $uid = $userinfo->id;
                $info = $this->model->where('id', $data['id'])->where('status', 1)->find();
                if (!$info) {
                    $this->error('资源错误');
                }
                $task = ResourceTask::where('uid', $uid)->where('rid', $info->id)->find();
                if (!$task) {
                    $this->error('没有任务记录');
                }
                if ($info->invite == 1 && ($task->invite_num < $info->invite_num)) {
                    $this->error('邀请任务未完成');
                }
                #todo 是否判断激励视频

                if (is_videoAd($this->plat_form)) {
                    if ($info->exc_video == 1 && !$task->is_video) {
                        $this->error('激励视频任务未完成');
                    }
                }
                #更新资源任务状态
                $task->status = 1;
                $task->utime = time();
                $result = $task->save();
                if (!$result) {
                    $this->error('任务未完成');
                }
                $myres = UserResource::where('uid', $uid)->where('rid', $info->id)->find();
                if ($myres) {
                    $this->error('请不要重复提交');
                }
                $info->sales = ['inc', 1];
                $info->save();
                $arr['rid'] = $info->id;
                $arr['uid'] = $uid;
                $arr['type'] = 2;
                if ((new UserResource)->save($arr)) {
                    //订阅提醒
                    $template_id = config('setting.subscribe_task_id');
                    $i = UserSubscribe::where(['temp_id' => $template_id, 'rid' => $info->id, 'uid' => $uid])->where('result', 'accept')->find();
                    if ($i) {
                        $openid = (new UserThird())->getFieldVal(1, $uid, 'openid');
                        $app = Facade::miniProgram();
                        $thing4 = '任务进度,邀请:' . $task->invite_num . '/' . $info->invite_num . ',视频:1/1';
                        $arr = [
                            'template_id' => $template_id,
                            'touser' => $openid,
                            'page' => '/pages/share/jump?fid=' . $task->admin_id . '&uid=' . $uid . '&type=2&id=' . $info->id . '&r_type=' . $info->type,
                            'data' => [
                                'thing3' => ['value' => '任务:' . mb_substr($info->title, 0, 11) . '...'],
                                'thing4' => ['value' => $thing4],
                                'phrase12' => ['value' => '已完成'],
                                'time14' => ['value' => date('Y.m.d H:i')]
                            ]
                        ];
                        $app->subscribe_message->send($arr);
                    }
                    $this->success('success');
                }
                $this->error('获取失败');
            }
        }
    }

    /**
     * 设置记录
     */
    protected function setLog($uid, $ip, $r_id, $admin_id)
    {
        $view = SpreadView::where(['ip' => $ip])->find();
        if (empty($view)) {
            SpreadView::create([
                'admin_id' => $admin_id,
                'r_id' => $r_id,
                'num' => 1,
                'ip' => $ip,
                'uid' => $uid,
                'ctime' => time()
            ]);
        }
        return [true, '记录成功'];
    }

    /**
     * 视频及课程播放记录
     */
    public function viewLog()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $validate = new Check;
            if (!$validate->scene('Resource.viewLog')->check($data)) {
                $this->error($validate->getError());
            }
            $viewLog = ViewLog::where(['rid' => $data['id'], 'cid' => $data['cid'], 'type' => $data['type'], 'uid' => $data['uid']])->find();
            if ($viewLog) {
                $viewLog->nums = ['inc', 1];
                $viewLog->utime = time();
                $result = $viewLog->save();
            } else {
                $datas = [
                    'rid' => $data['id'],
                    'cid' => $data['cid'],
                    'type' => $data['type'],
                    'admin_id' => $data['from_id'],
                    'uid' => $data['uid'],
                    'nums' => 1,
                    'ctime' => time()
                ];
                $result = ViewLog::create($datas, true);
            }
            if (!$result) {
                $this->error('记录失败');
            }
            $this->success('success');
        }
        $this->error('记录失败');
    }

    /**
     * 用户助力
     * @return void
     */
    public function handSpread()
    {
        try {
            if (request()->isPost()) {
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('Resource.handSpread')->check($data)) {
                    $this->error($validate->getError());
                }
                #查询要确认是否正常
                $invite_user=User::where(['id'=>$data['r_uid'],'status'=>1])->find();
                if(!$invite_user){
                    $this->error('邀请任务已失效,助力失败');
                }
                $inviter=[
                    'id'=>$invite_user->id,
                    'nickname'=>$invite_user->nickname,
                    'avatar'=>$invite_user->avatar,
                ];
                $spread_hand_type = intval(config('setting.spread_hand_type'));
                if ($spread_hand_type == 1) {
                    $userinfo = User::where(['token' => $data['token'], 'status' => 1])->find();
                    if (empty($userinfo)) {
                        $this->error('用户未登录或账号异常');
                    }
                    $uid = $userinfo->id;
                    if($uid===$data['r_uid']){
                        $this->error('助力人不能是邀请人自己,助力失败');
                    }
                    list($res, $msg) = (new SpreadHand())->handSpread($data['r_uid'], $uid, $userinfo->admin_id, $data['id']);
                    if (!$res) {
                        $this->error($msg);
                    }
                    $this->success('助力成功',$inviter);
                }
                $this->success('助力成功',$inviter);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 用户助力列表
     * @return void
     */
    public function getHandList()
    {
        try {
            if (request()->isPost()) {
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('Resource.getHandList')->check($data)) {
                    $this->error($validate->getError());
                }
                $start = ($data['page'] - 1) * $data['limit'];
                $spread_hand_type = intval(config('setting.spread_hand_type'));
                if ($spread_hand_type == 1) {
                    $list = SpreadHand::with(['user' => ['avatar', 'nickname']])->where(['spread_hand.rid'=>$data['id'],'r_uid'=>$data['r_uid']])->limit($start, $data['limit'])->order('spread_hand.ctime desc')->select();
                } else {
                    $list = Invite::with(['user' => ['avatar', 'nickname']])->where(['invite.rid'=>$data['id'],'invite.pid'=>$data['r_uid']])->limit($start, $data['limit'])->order('invite.ctime desc')->select();
                }
                foreach ($list as $k => $v) {
                    $list[$k]['update']=formatTime(strtotime($v['ctime']));
                }
                $this->success('success', ['list' => $list]);
            }
            $this->error('请求失败');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}