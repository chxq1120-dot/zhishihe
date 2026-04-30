<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2023/6/15
 * Time: 16:53
 */

namespace app\index\controller;

use app\api\validate\Check;
use app\common\controller\Common;
use app\common\lib\Xunhu;
use app\common\model\Admin;
use app\common\model\AdminBill;
use app\common\model\AdminSite;
use app\common\model\Article;
use app\common\model\ArticleSort;
use app\common\model\AudioCourse;
use app\common\model\Bill;
use app\common\model\Kammi;
use app\common\model\Order;
use app\common\model\Privilege;
use app\common\model\ResourceKammi;
use app\common\model\ResourceLevel;
use app\common\model\ResourceSort;
use app\common\model\ResourceTask;
use app\common\model\Spread;
use app\common\model\SpreadGroup;
use app\common\model\SpreadJump;
use app\common\model\SpreadType;
use app\common\model\SpreadView;
use app\common\model\Svip;
use app\common\model\SvipPrivilege;
use app\common\model\User;
use app\common\model\UserColl;
use app\common\model\UserQrcode;
use app\common\model\UserResource;
use app\common\model\Validate;
use app\common\model\VideoCourse;
use Endroid\QrCode\QrCode;
use Naixiaoxin\ThinkWechat\Facade;
use think\Db;
use think\Exception;
use zp\Tree;
use org\File;
use think\facade\Env;

class Index extends Common
{
    protected $admin_id = 0;

    public function initialize()
    {
        parent::initialize();
        $lock = Env::get('root_path') . 'public/data/install.lock';
        if (!File::has($lock)) {
            $this->redirect(url('/install'));
        }
        #设置默认代理ID
        $admin_id = $this->request->param('from/d', 0);
        if (!empty($admin_id)) {
            cookie('site_id', $admin_id);
        }
        #邀请人ID
        $invite_uid = $this->request->param('invite_uid/d', 0);
        if (!empty($invite_uid)) {
            cookie('invite_uid', $invite_uid);
        }
        #资源ID
        $rid = $this->request->param('rid/d', 0);
        if (!empty($rid)) {
            cookie('r_id', $rid);
        }
        if (empty($admin_id)) {
            $admin_id = (new Admin())->getDefaultAdminId();
        }
        $this->admin_id = empty(cookie('site_id')) ? $admin_id : cookie('site_id');
        #获取分站配置
        $site_info = [
            'web' => [
                'webid' => $this->admin_id,
                'name' => config('setting.web_name'),
                'contact' => config('setting.web_contact'),
                'wechat' => config('setting.web_wechat'),
                'qrcode' => config('setting.web_qrcode'),
                'phone' => config('setting.web_phone'),
            ]
        ];
        $agent = Admin::where('id', $this->admin_id)->find();
        if (!empty($agent)) {
            if (!empty($agent->webname)) {
                $site_info['web']['name'] = $agent->webname;
            }
            if (!empty($agent->realname)) {
                $site_info['web']['contact'] = $agent->realname;
            }
            if (!empty($agent->qrcode)) {
                $site_info['web']['qrcode'] = $agent->qrcode;
            }
            if (!empty($agent->wechat)) {
                $site_info['web']['wechat'] = $agent->wechat;
            }
            if (!empty($agent->mobile)) {
                $site_info['web']['phone'] = $agent->mobile;
            }
            $siteModel = new AdminSite();
            $site = $siteModel->where('admin_id', $this->admin_id)->find();
            if (!empty($site)) {
                $site_info['web']['name'] = $site->webname;
                $site_info['web']['contact'] = $site->realname;
                $site_info['web']['qrcode'] = $site->qrcode;
                $site_info['web']['wechat'] = $site->wechat;
                $site_info['web']['phone'] = $site->mobile;
            }
            $site_info['web']['url'] = $siteModel->getSiteUrl($this->admin_id);
        }
        $this->assign('subsite', $site_info);
        $this->assign('admin_id', $this->admin_id);
    }

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        return $this->tpl_fetch('/index');
    }
    /**
     * 会员特权
     * @return mixed
     */
    public function svip()
    {
        $sviplist = Svip::field('id,name,days,price,invite_num,content')->where(['type' => 0, 'status' => 1,'admin_id'=>$this->admin_id])->select();
        foreach ($sviplist as $k => $v) {
            $sviplist[$k]['days'] = $v['days'] . '天';
            if ($v['days'] >= 9999) {
                $sviplist[$k]['days'] = '永久';
            }
        }
        $svip_article_id = empty(config('setting.vip_article_id')) ? 0 : config('setting.vip_article_id');
        $svip_info = Article::where('id', $svip_article_id)->find();
        $this->assign('sviplist', $sviplist);
        $this->assign('svipinfo', $svip_info);
        $this->assign('title', '会员特权 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/svip');
    }

    /**
     * 文章列表
     * @return mixed
     */
    public function article()
    {
        $sort_id = $this->request->param('sortid/d', 0);
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $where[] = ['article.status', 'eq', 1];
        $sort_name = '精选文章';
        $map = ['article.sort_id' => 6];
        if (!empty($sort_id)) {
            $map = ['article.sort_id' => $sort_id];
            $sort_name = ArticleSort::where('id', $sort_id)->value('name');
        }
        $list = Article::with(['sort' => ['name']])->where($where)->where($map)->order('article.ctime desc')->paginate($limit);
        $page = $list->render();
        if (!empty($sort_id)) {
            $navPath = ' &gt; <a href="/article" title="精选文章">精选文章</a> &gt; <a href="/article/list' . $sort_id . '" title="' . $sort_name . '">' . $sort_name . '</a>';
        } else {
            $navPath = ' &gt; 精选文章';
        }
        foreach ($list as $k => $v) {
            $list[$k]['ctime_text'] = date('Y-m-d', strtotime($v['ctime_text']));
            $list[$k]['desc'] = str_cut(str_replace('&nbsp;','',strip_tags($v['content'])), 50, '') . '...';
        }
        $this->assign('sort_name', $sort_name);
        $this->assign('navPath', $navPath);
        $this->assign('article_list', $list);
        $this->assign('page', $page);
        $this->assign('title', $sort_name . ' - ' . config('setting.coms_title'));
        $this->assign('keyword', $sort_name . ',' . config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/article_list');
    }

    /**
     * 文章详情
     * @return mixed
     */
    public function article_show()
    {
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $article = Article::with(['sort' => ['name']])->where('article.id', $id)->find();
        if (empty($article)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        Article::where('id', $id)->update(['views' => Db::raw('views+1'), 'utime' => time()]);
        $this->assign('article', $article);
        $this->assign('title', $article->title . ' - ' . config('setting.coms_title'));
        $this->assign('keyword', $article->title . ',' . config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/article_show');
    }

    /**
     * 帮助中心
     * @return mixed
     */
    public function help()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $where[] = ['status', 'eq', 1];
        $map = ['sort_id' => 2];
        $list = Article::where($where)->where($map)->order('ctime desc')->paginate($limit);
        $page = $list->render();
        $navPath = ' &gt; 帮助中心';
        foreach ($list as $k => $v) {
            $list[$k]['ctime_text'] = date('Y-m-d', strtotime($v['ctime_text']));
            $list[$k]['desc'] = str_cut(str_replace('&nbsp;','',strip_tags($v['content'])), 50, '') . '...';
        }
        $this->assign('navPath', $navPath);
        $this->assign('help_list', $list);
        $this->assign('page', $page);
        $this->assign('title', '帮助中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/help');
    }

    /**
     * 帮助详情
     * @return mixed
     */
    public function help_show()
    {
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $article = Article::with(['sort' => ['name']])->where('article.id', $id)->find();
        if (empty($article)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        Article::where('id', $id)->update(['views' => Db::raw('views+1'), 'utime' => time()]);
        $this->assign('article', $article);
        $this->assign('title', $article->title . ' - ' . config('setting.coms_title'));
        $this->assign('keyword', $article->title);
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/help_show');
    }

    /**
     * 关于我们
     * @return mixed
     */
    public function about()
    {
        $id = $this->request->param('id/d', 8);
        $article = Article::with(['sort' => ['name']])->where('article.id', $id)->find();
        if (empty($article)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        Article::where('id', $id)->update(['views' => Db::raw('views+1'), 'utime' => time()]);
        $this->assign('article', $article);
        $this->assign('title', $article->title . ' - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/about');
    }

    /**
     * 课程列表
     * @return mixed
     */
    public function course()
    {
        $sid = $this->request->param('sid/d', 0);
        $type = $this->request->param('ty/d', 0);
        $level = $this->request->param('le/d', 0);
        $price = $this->request->param('pe/d', 0);
        #类型
        if (!empty($type)) {
            $map[] = ['a.type', '=', $type];
        }
        #等级
        if (!empty($level)) {
            $map[] = ['a.level', '=', $level];
        }
        #价格
        if (!empty($price)) {
            if ($price == 1) {
                $map[] = ['a.price', '=', 0];
            } else {
                $map[] = ['a.price', '>', 0];
            }
        }
        $sort_name = '全部课程';

        #关键字
        $keys = $this->request->param('keys/s');
        if (!empty($keys)) {
            $map[] = ['a.title|a.desc', 'like', '%' . $keys . '%'];
        }

        $map[] = ['a.admin_id', '=', $this->admin_id];
        $map[] = ['a.status', '=', 1];
        $field = 'a.id,a.title,a.thumb,a.type,a.price,a.dis_price,a.desc,a.sales,a.level,a.utime,a.ctime';
        $list = Spread::alias('a')->field($field);
        #分类
        if (!empty($sid)) {
            $sortModel = new ResourceSort();
            $sort_arr = $sortModel->where('status', 1)->order('indexid asc')->select();
            if ($sort_arr) {
                $sort_arr = $sort_arr->toArray();
                $tree = new Tree();
                $tree->init($sort_arr, 'pid');
                $child_ids = $tree->getChildrenIds($sid, true);
                $list->join('spread_type b', 'a.id=b.rid');
                $map[] = ['b.sid', 'in', $child_ids];
                $sort_name = $sortModel->where('id', $sid)->value('name');
            }
        }
        $list = $list->where($map)->order('a.ctime desc')->paginate(20);
        $page = $list->render();
        if (!empty($sid)) {
            $navPath = ' &gt; <a href="/course/list' . $sid . '" title="' . $sort_name . '">' . $sort_name . '</a>';
        } else {
            $navPath = ' &gt; 课程列表';
        }
        $this->assign('navPath', $navPath);
        $this->assign('sort_name', $sort_name);
        $this->assign('level_list', ResourceLevel::where(['status' => 1])->select());
        $this->assign('sid', $sid);
        $this->assign('level', $level);
        $this->assign('price', $price);
        $this->assign('type', $type);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->assign('title', $sort_name . ' - ' . config('setting.coms_title'));
        $this->assign('keyword', $sort_name . ',' . config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/list');
    }

    /**
     * 资源详情页
     * @return mixed
     */
    public function show()
    {
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $field = 'a.id,a.thumb,a.title,a.level,a.link,a.ext_code,a.type,a.price,a.dis_price,a.desc,a.sales,a.invite,a.invite_num,a.exc_video,a.try_see,a.rid,b.free_content,b.content';
        $info = Spread::alias('a')->join('resource_info b', 'a.rid=b.rid')->where('a.id', $id)->field($field)->where('a.status', 1)->find();
        if (!$info) {
            $this->error('资源获取失败');
        }
        $info->views = ['inc', 1];
        $info->save();
        $info->level_name = (new ResourceLevel())->getLevelName($info->level);
        #获取手机端访问链接
        $mobile_url = getMobileShowUrl($info->type, $id);
        #查询所属分类
        $spread_sorts = SpreadType::alias('a')->join('resource_sort b', 'a.sid=b.id')->field('b.id,b.name')->where(['a.rid' => $id])->order('a.id asc')->select()->toArray();
        if (!empty($spread_sorts)) {
            $info->sorts = $spread_sorts[0];
        } else {
            $info->sorts = [];
        }
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
        #用户查看权限
        $info->is_auth = 0;
        $info->percent = 0;
        $info->is_subscribe = 0;
        $info->re_price = 0;#折扣价
        $info->is_coll = 0;
        $uid = 0;
        $user = cookie('user');
        if (!empty($user)) {//已登录获取收藏和任务数据
            $user = \app\common\model\User::where(['id' => $user['id'], 'status' => 1])->find();
            $uid = $user['id'];
            $iscoll = UserColl::where('uid', $uid)->where('rid', $info->id)->find();
            if ($iscoll) {//是否收藏
                $info->is_coll = 1;
            } else {
                $info->is_coll = 0;
            }
            #todo 判断是否有会员权限
            if ($user['vid'] > 0 && $user['exp_time'] > time()) {
                $is_level = 0;
                #等级
                $level = Privilege::handleUserAuth($user['vid'], $info->level, 1);
                if ($level) {
                    $is_level = $level;
                }
                #数量
                $is_limits = 0;
                $today_num=UserResource::where(['uid'=>$user->id,'type'=>3])->whereTime('ctime', 'today')->count();
                $limits = Privilege::handleUserAuth($user['vid'], $today_num, 2);
                if ($limits) {
                    $is_limits = $limits;
                }
                #分类
                $is_sorts = 0;
                $sort_ids = SpreadType::where('rid', $info->id)->column('sid');
                $sorts = Privilege::handleUserAuth($user['vid'], $sort_ids, 3);
                if ($sorts) {
                    $is_sorts = $sorts;
                }
                #判断
                if ($is_level == 2 || $is_limits == 2 || $is_sorts == 2) {
                    $info->is_auth = 1;
                }
                if ($info->is_auth = 1) {
                    if ($is_level == 1 || $is_limits == 1 || $is_sorts == 1) {
                        $info->is_auth = 0;
                    }
                }
                #判断是否设置了会员折扣价
                $dis_price = \app\common\model\Svip::getDiscountPrice($user['vid'], $info->price);
                if ($info->is_auth == 1 && $dis_price > 0) {
                    $info->re_price = $dis_price;
                    $info->is_auth = 0;
                }
            }
            #todo 判断是否购买了资源或完成了任务
            $auth = UserResource::where('rid', $info->id)->where('uid', $uid)->find();
            if ($auth) {
                $info->is_auth = 1;
            } else {
                #没有查询到记录
                if (empty(floatval($info->price))) {
                    if (empty($info->invite) && empty($info->exc_video)) {
                        $info->is_auth = 1;
                    }
                    #PC端
                    if(!isMobile() && empty($info->invite)){
                        $info->is_auth = 1;
                    }
                }
            }
        }
        #精品推荐
        $where[] = ['a.id', '<>', $id];
        if (!empty($info->sorts)) {
            $sortModel = new ResourceSort();
            $sort_arr = $sortModel->where('status', 1)->order('indexid asc')->select();
            if ($sort_arr) {
                $sort_arr = $sort_arr->toArray();
                $tree = new Tree();
                $tree->init($sort_arr, 'pid');
                $child_ids = $tree->getChildrenIds($info->sorts['id'], true);
                $where[] = ['b.sid', 'in', $child_ids];
            } else {
                $where[] = ['b.sid', '=', $info->sorts['id']];
            }
        }
        #判断文章文库后缀
        $icon_ext = '';
        if ($info->type == 3 && !empty($info->ext_code)) {
            switch ($info->ext_code) {
                case 'doc':
                case 'txt':
                case 'docx':
                    $icon_ext = 'icon-doc';
                    break;
                case 'xls':
                case 'xlsx':
                    $icon_ext = 'icon-xls';
                    break;
                case 'pdf':
                    $icon_ext = 'icon-pdf';
                    break;
                case 'rar':
                case 'zip':
                    $icon_ext = 'icon-rar';
                    break;
                case 'ppt':
                case 'pptx':
                    $icon_ext = 'icon-ppt';
                    break;
                default:
                    $icon_ext = 'icon-file';
                    break;
            }
        }
        $topInfo = Spread::alias('a')->join('spread_type b', 'a.id=b.rid')->where(['a.status' => 1, 'a.is_top' => 1, 'a.admin_id' => $this->admin_id])->where($where)->order('a.ctime desc')->limit(8)->select();
        #记录浏览记录
        $ip = $this->request->ip(0, true);
        $this->setLog($uid, $ip, $id, $this->admin_id);
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
        $this->assign('icon_ext', $icon_ext);
        $this->assign('topInfo', $topInfo);
        $this->assign('spread', $info);
        $this->assign('sorts_list', $spread_sorts);
        $this->assign('mobile_url', $mobile_url);
        $this->assign('title', $info->title . ' - 全部课程 - ' . config('setting.coms_title'));
        $this->assign('keyword', $info->title . ',' . config('setting.coms_keys'));
        $this->assign('desc', $info->desc . ',' . config('setting.coms_desc'));
        return $this->tpl_fetch('/show');
    }

    /**
     * 视频课程播放检测
     */
    public function checkVideo()
    {
        try {
            if ($this->request->isAjax()) {
                $spread_id = $this->request->param('rid/d', 0);
                if (empty($spread_id)) {
                    $this->error('课程ID不能为空');
                }
                $video_id = $this->request->param('vid/d', 0);
                $user = cookie('user');
                if (empty($user)) {
                    $this->result('', 2, '请登录后再操作');
                }
                $is_auth = $this->userAuth($user['id'], $spread_id);

                $spread=Spread::where(['id'=>$spread_id,'status'=>1])->find();
                if(empty($spread)){
                    $this->error('课程已经下架了');
                }
                if($spread->type==4){
                    if(empty($video_id)){
                        $video = VideoCourse::where('rid',$spread->rid)->order('is_try desc,indexid asc')->find();
                        if (empty($video)) {
                            $this->error('课程已经下架了');
                        }
                    }else{
                        $video = VideoCourse::where('id', $video_id)->find();
                        if (empty($video)) {
                            $this->error('课程已经下架了');
                        }
                    }
                    if ($is_auth == 0 && $video->is_try == 0) {
                        $this->result('', 3, '您还没有权限观看课程');
                    }
                    $this->success('success', '', ['is_auth' => $is_auth, 'is_try' => $video->is_try, 'url' => $video->url,'video_id'=>$video->id]);
                }else{
                    $this->success('success', '', ['is_auth' => $is_auth, 'is_try' => 1, 'url' => $spread->link,'video_id'=>0]);
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 拼音课程播放检测
     */
    public function checkAudio()
    {
        try {
            if ($this->request->isAjax()) {
                $spread_id = $this->request->param('rid/d', 0);
                if (empty($spread_id)) {
                    $this->error('资源ID不能为空');
                }
                $spread_rid = $this->request->param('r_id/d', 0);
                if (empty($spread_rid)) {
                    $this->error('资源ID不能为空');
                }
                $video_id = $this->request->param('vid/d', 0);
                $user = cookie('user');
                if (empty($user)) {
                    $this->result('', 2, '请登录后再操作');
                }
                $is_auth = $this->userAuth($user['id'], $spread_id);
                if (empty($video_id)) {
                    $video = AudioCourse::where(['rid' => $spread_rid])->order('is_try desc,indexid asc')->find();
                    if (empty($video)) {
                        $this->error('课程已经下架了');
                    }
                } else {
                    $video = AudioCourse::where(['id' => $video_id])->find();
                    if (empty($video)) {
                        $this->error('课程已经下架了');
                    }
                }
                if ($is_auth == 0 && $video->is_try == 0) {
                    $this->result('', 3, '您还没有权限收听课程');
                }
                $this->success('success', '', ['is_auth' => $is_auth, 'is_try' => $video->is_try, 'url' => $video->url, 'name' => $video->title,'audio_id'=>$video->id]);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 视频课程音频课程播放
     * @return mixed
     */
    public function video()
    {
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $vid = $this->request->param('vid/d', 0);
        $field = 'id,thumb,title,level,link,ext_code,type,price,dis_price,desc,is_show,sales,invite,invite_num,exc_video,try_see,rid';
        $info = Spread::where('id', $id)->field($field)->where('status', 1)->find();
        if (!$info) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $info->save();
        #查询所属分类
        $sorts = SpreadType::alias('a')->join('resource_sort b', 'a.sid=b.id')->field('b.id,b.name')->where(['a.rid' => $id])->order('a.id asc')->select()->toArray();
        if (!empty($sorts)) {
            $info->sorts = $sorts[0];
        } else {
            $info->sorts = [];
        }
        #查询课程视频列表
        $info->video_list = [];
        $info->video_num = 1;
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
        #用户查看权限
        $info->is_auth = 0;
        $info->re_price = 0;
        $user = cookie('user');
        if (!empty($user)) {
            $info->is_auth = $this->userAuth($user['id'], $info->id);
            #判断是否设置了会员折扣价
            $dis_price = \app\common\model\Svip::getDiscountPrice($user['vid'], $info->price);
            if ($info->is_auth == 1 && $dis_price > 0) {
                $info->re_price = $dis_price;
                $info->is_auth = 0;
            }
        }
        #精品推荐
        $where[] = ['a.id', '<>', $id];
        if (!empty($info->sorts)) {
            $sortModel = new ResourceSort();
            $sort_arr = $sortModel->where('status', 1)->order('indexid asc')->select();
            if ($sort_arr) {
                $sort_arr = $sort_arr->toArray();
                $tree = new Tree();
                $tree->init($sort_arr, 'pid');
                $child_ids = $tree->getChildrenIds($info->sorts['id'], true);
                $where[] = ['b.sid', 'in', $child_ids];
            } else {
                $where[] = ['b.sid', '=', $info->sorts['id']];
            }
        }
        $topInfo = Spread::alias('a')->join('spread_type b', 'a.id=b.rid')->where(['a.status' => 1, 'a.is_top' => 1, 'a.admin_id' => $this->admin_id])->where($where)->order('a.ctime desc')->limit(8)->select();
        $this->assign('topInfo', $topInfo);
        $this->assign('spread', $info);
        $this->assign('vid', $vid);
        $this->assign('title', $info->title . ' - 全部课程 - ' . config('setting.coms_title'));
        $this->assign('keyword', $info->title . ',' . config('setting.coms_keys'));
        $this->assign('desc', $info->desc . ',' . config('setting.coms_desc'));
        if ($info->type == 4 || $info->type == 2) {
            return $this->tpl_fetch('/video');
        } else {
            return $this->tpl_fetch('/audio');
        }
    }

    /**
     * 用户是否获得资源权限
     */
    protected function userAuth($uid, $id)
    {
        $is_auth = 0;
        $info = Spread::where(['id' => $id, 'status' => 1])->find();
        if (empty($info)) {
            return $is_auth;
        }
        $user = \app\common\model\User::where(['id' => $uid, 'status' => 1])->find();
        if ($user['vid'] > 0 && $user['exp_time'] > time()) {
            $is_level = 0;
            #等级
            $level = Privilege::handleUserAuth($user['vid'], $info->level, 1);
            if ($level) {
                $is_level = $level;
            }
            #数量
            $is_limits = 0;
            $today_num=UserResource::where(['uid'=>$user->id,'type'=>3])->whereTime('ctime', 'today')->count();
            $limits = Privilege::handleUserAuth($user['vid'], $today_num, 2);
            if ($limits) {
                $is_limits = $limits;
            }
            #分类
            $is_sorts = 0;
            $sort_ids = SpreadType::where('rid', $info->id)->column('sid');
            $sorts = Privilege::handleUserAuth($user['vid'], $sort_ids, 3);
            if ($sorts) {
                $is_sorts = $sorts;
            }
            #判断
            if ($is_level == 2 || $is_limits == 2 || $is_sorts == 2) {
                $is_auth = 1;
            }
            if ($is_auth = 1) {
                if ($is_level == 1 || $is_limits == 1 || $is_sorts == 1) {
                    $is_auth = 0;
                }
            }
        }
        #todo 判断是否购买了资源或完成了任务
        $auth = UserResource::where('rid', $info->id)->where('uid', $uid)->find();
        if ($auth) {
            $is_auth = 1;
        } else {
            #没有查询到记录
            if (empty(floatval($info->price))) {
                if (empty($info->invite) && empty($info->exc_video)) {
                    $is_auth = 1;
                }
            }
        }
        return $is_auth;
    }

    /**
     * 显示推广任务
     */
    public function subtask()
    {
        $id = $this->request->param('id/d', 0);
        $user = cookie('user');
        if (empty($user)) {
            $this->error('请登录后再操作');
        }
        $spread = Spread::where('id', $id)->where('status', 1)->find();
        if (!$spread) {
            $this->error('资源已下线');
        }
        $task = ResourceTask::where('uid', $user['id'])->where('rid', $id)->find();
        if (!$task) {
            $data = [
                'admin_id' => $this->admin_id,
                'rid' => $id,
                'uid' => $user['id'],
                'ctime' => time(),
                'invite_num' => 0,
                'is_video' => 0,
            ];
            $task = ResourceTask::create($data, true);
        }
        $task->percent = 0;
        $task->invited = $spread->invite_num; #需要邀请的数量
        if (!empty($task->invite_num) && !empty($spread->invite_num)) {
            $task->percent = round(($task->invite_num / $spread->invite_num) * 100);
        }
        $invite_url = $this->request->domain() . '/u_' . $user['id'] . '_' . $id;
        $this->assign('task', $task);
        $this->assign('invite_url', $invite_url);
        $this->assign('spread', $spread);
        return $this->tpl_fetch('/task');
    }

    /**
     * 支付结果页
     *
     */
    public function pay_result()
    {
        $ordno = $this->request->param('out_trade_no/s');
        if (empty($ordno)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $order = Order::where('ordno', $ordno)->find();
        if ($order->type == 1) {
            $subject = Spread::where('id', $order->rid)->value('title');
            $jump_url = $this->request->domain() . '/course/show/' . $order->rid;
        } else {
            $subject = Svip::where('id', $order->vid)->value('name');
            $jump_url = $this->request->domain() . '/user/mysvip';
        }
        $this->assign('jump_url', $jump_url);
        $this->assign('subject', $subject);
        $this->assign('order', $order);
        $this->assign('title', '支付结果 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/result');
    }

    /**
     * 确认订单
     * @return mixed
     */
    public function order()
    {
        if ($this->request->isAjax()) {
            try {
                if (empty(cookie('user'))) {
                    $this->error('请登录后再操作');
                }
                $type = $this->request->post('type/d', 1);
                $rid = $this->request->post('rid/d', 0);
                if (empty($rid)) {
                    $this->error('请选择支付套餐');
                }
                $paytype = $this->request->post('paytype/d', 1);
                $money = $this->request->post('money/f', 0);
                if (empty($money)) {
                    $this->error('请选择支付套餐');
                }
                $cardno = $this->request->post('cardno/s');
                if ($paytype == 3 && empty($cardno)) {
                    $this->error('请输入卡密');
                }
                $data['ordno'] = date('YmdHis') . mt_rand(10000, 99999);
                $uid = cookie('user')['id'];
                $userinfo = \app\common\model\User::where('id', $uid)->find();
                if ($type == 1) {#购买资源
                    $spread = Spread::where('id', $rid)->find();
                    if (!$spread) {
                        $this->error('资源不存在');
                    }
                    if ($spread->status == 0) {
                        $this->error('资源已下架');
                    }
                    if ($spread->price == 0) {
                        $this->error('资源价格不能为零');
                    }
                    $dis_price = Svip::getDiscountPrice($userinfo->vid, $spread->price);
                    $data['money'] = $dis_price > 0 ? $dis_price : $spread->price;
                    $data['rid'] = $spread->id;
                    $data['r_id'] = $spread->rid;
                    $data['spread_type'] = $spread->type;
                    $data['body'] = '购买资源';
                } else {#购买VIP
                    $svip = Svip::where('id', $rid)->find();
                    if (!$svip) {
                        $this->error('会员套餐不存在');
                    }
                    $data['money'] = $svip->price;
                    $data['vid'] = $svip->id;
                    $data['body'] = '购买会员';
                }
                if (!empty($userinfo->pid)) {//判断合伙人
                    $pinfo = User::where('id', $userinfo->pid)->find();
                    if ($pinfo) {
                        if (time() < $pinfo->exp_time) {
                            $rule = Privilege::handleUserAuth($pinfo->vid, '', 5);
                            if ($rule) {
                                $arr['hr_money'] = bcmul($rule, $data['money'], 2);
                            }
                        }
                    }
                }
                $data['admin_id'] = $userinfo->admin_id;
                $data['pid'] = $userinfo->pid;
                $data['uid'] = $uid;
                $data['nickname'] = $userinfo->nickname;
                $data['type'] = $type;
                Db::startTrans();
                $data['return_url'] = $this->request->domain() . '/paycenter/result';
                switch ($paytype) {
                    case 1:#支付宝
                        $data['platform'] = 'ALIPAY_PC';
                        $data['pay_type'] = 2;
                        if (!(new Order())->allowField(true)->save($data)) {
                            Db::rollback();
                            $this->error('下单购买失败');
                        }
                        $wechat_pay_type=intval(config('setting.wechat_pay_type'));
                        switch ($wechat_pay_type) {
                            case 1:#支付宝官方支付商户号
                                $aliPay = new \alipay\wap();
                                $aliPay->setAppid(config('setting.ali_pay_appid'));
                                $aliPay->setReturnUrl($data['return_url']);
                                $aliPay->setNotifyUrl(config('setting.ali_pay_notify'));
                                $aliPay->setRsaPrivateKey(config('setting.ali_pay_private_key'));
                                $aliPay->setTotalFee($data['money']);
                                $aliPay->setOutTradeNo($data['ordno']);
                                $aliPay->setOrderName($data['body']);
                                $aliPay->setProName('alipay.trade.page.pay');
                                $aliPay->setProCode('FAST_INSTANT_TRADE_PAY');
                                $result = $aliPay->doPay();
                                $pay_url = $result;
                                Db::commit();
                                $this->success('success', '', ['pay_url' => $pay_url, 'ordno' => $data['ordno']]);
                                break;
                            case 2:#支付宝官方支付
                                $payment = new Xunhu();
                                $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                list($result, $response) = $payment->createPay('alipay', 'wap', $data['ordno'], $data['money'],
                                    $data['body'],
                                    $notify_url
                                );
                                if (!$result) {
                                    Db::rollback();
                                    $this->error($response);
                                }
                                Db::commit();
                                $this->success('success', '',['pay_url' => $response['url'],'ordno' => $data['ordno']]);
                                break;
                        }
                        break;
                    case 2:#微信
                        $data['platform'] = 'WECHAT_PC';
                        $data['pay_type'] = 1;
                        if (!(new Order())->allowField(true)->save($data)) {
                            Db::rollback();
                            $this->error('下单购买失败');
                        }
                        $wechat_pay_type=intval(config('setting.wechat_pay_type'));
                        switch ($wechat_pay_type){
                            case 1:#微信支付商户号
                                $order = [
                                    'trade_type' => 'NATIVE',
                                    'product_id' => $rid,
                                    'total_fee' => $data['money'] * 100,
                                    'out_trade_no' => $data['ordno'],
                                    'body' => $data['body'],
                                    'notify_url' => config('setting.wxpay_notify')
                                ];
                                $payment = Facade::payment('official_account'); // 微信支付
                                $result = $payment->order->unify($order);
                                if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                                    Db::commit();
                                    $this->success('success', '', ['pay_url' => $result['code_url'], 'ordno' => $data['ordno']]);
                                } else {
                                    Db::rollback();
                                    $this->error('下单失败');
                                }
                                break;
                            case 2:#虎皮椒微信支付
                                $payment = new Xunhu();
                                $notify_url = $this->request->domain() . '/index/Notify/xunNotify';
                                list($result, $response) = $payment->createPay('wechat', 'wap', $data['ordno'], $data['money'],
                                    $data['body'],
                                    $notify_url
                                );
                                if (!$result) {
                                    Db::rollback();
                                    $this->error($response);
                                }
                                Db::commit();
                                $this->success('success', '',['pay_url' => $response['url_qrcode'],'ordno' => $data['ordno']]);
                                break;
                        }
                        break;
                    case 3:#卡密
                        $data['platform'] = 'CDKEY';
                        $data['cdkey'] = $cardno;
                        list($res, $msg) = $this->handleCdkey($type, $data);
                        if (!$res) {
                            Db::rollback();
                            $this->error($msg);
                        }
                        Db::commit();
                        $this->success('success', '', ['ordno' => $data['ordno']]);
                        break;
                }
            } catch (Exception $e) {
                doSyslog($e->getMessage(), 'order');
                doSyslog($e->getLine(), 'order');
                doSyslog($e->getTraceAsString(), 'order');
                $this->error($e->getMessage());
            }
        }
        $id = $this->request->param('id/d', 0);
        if (empty($id)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $spread = Spread::where('id', $id)->find();
        if (empty($spread)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $user = cookie('user');
        if (empty($user)) {
            header('Location:' . $this->request->domain() . '/login?fromurl=' . urlencode($this->request->url(true)));
            exit;
        }
        $user = \app\common\model\User::where('id', $user['id'])->find();
        $spread->re_price = \app\common\model\Svip::getDiscountPrice($user['vid'], $spread->price);
        #查询所属分类
        $sorts = SpreadType::alias('a')->join('resource_sort b', 'a.sid=b.id')->field('b.id,b.name')->where(['a.rid' => $id])->order('a.id asc')->find();
        $spread->sorts = $sorts;
        $field = 'id,name,days,price,content,invite_num';
        $svip_list = Svip::field($field)->where(['type' => 0, 'status' => 1,'admin_id'=>$this->admin_id])->order('level asc')->select();
        foreach ($svip_list as $k => $v) {
            $svip_list[$k]['days'] = $v['days'] . '天';
            if ($v['days'] >= 9999) {
                $svip_list[$k]['days'] = '永久';
            }
        }
        #支付类型
        $paytype=3;#卡密支付
        $alipay_open=intval(config('setting.alipay_open'));
        if($alipay_open==1){
            $paytype=1;
        }
        $wechat_open=intval(config('setting.wechat_open'));
        if($alipay_open==2 && $wechat_open==1){
            $paytype=2;
        }
        $this->assign('paytype', $paytype);
        $this->assign('svip_list', $svip_list);
        $this->assign('spread', $spread);
        $this->assign('title', '确定订单 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/order');
    }


    /**
     * 微信扫码支付
     * @return mixed
     */
    public function wechat()
    {
        $ordno = $this->request->param('ordno/s');
        if (empty($ordno)) {
            header('Location:' . $this->request->domain());
            exit;
        }
        $code = $this->request->param('code/s');
        $order = Order::where('ordno', $ordno)->find();
        $wechat_pay_type=intval(config('setting.wechat_pay_type'));
        $this->assign('order', $order);
        $this->assign('code', $code);
        $this->assign('wechat_pay_type', $wechat_pay_type);
        $this->assign('title', '微信支付 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/pay_wechat');
    }

    /**
     * 输出二维码
     */
    public function qrcode()
    {
        $text = $this->request->param('txt/s');
        if (empty($text)) {
            $this->error('二维码内容不能为空');
        }
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setWriterByName('png');
        header('Content-Type: ' . $qrCode->getContentType());
        echo $qrCode->writeString();
        exit;
    }

    /**
     * 查询订单支付支付状态
     */
    public function query_order()
    {
        $ordno = $this->request->param('ordno/s');
        if (empty($ordno)) {
            $this->error('订单号不能为空');
        }
        $order = Order::where('ordno', $ordno)->find();
        if (empty($order)) {
            $this->error('订单号不存在');
        }
        if ($order->type == 1) {
            $jump_url = $this->request->domain() . '/course/show/' . $order->rid;
        } else {
            $jump_url = $this->request->domain() . '/user/mysvip';
        }
        $this->success('succcess', '', ['pay_status' => $order->status, 'url' => $jump_url]);
    }

    /**
     * 卡密购买处理
     * @param $type int 类型 1资源，2vip
     * @param $cdkey string 卡密
     * @param $arr array 订单数据
     * @return array 数组
     */
    protected function handleCdkey($type, $arr)
    {
        try {
            if (!isset($arr['cdkey'])) {
                return [false, '卡密不存在或已经使用'];
            }
            $kammi = Kammi::where('cdkey', $arr['cdkey'])->find();
            if (!$kammi) {
                return [false, '卡密不存在或已经使用'];
            }
            if ($kammi['status'] !== 0) {
                return [false, '卡密已经使用'];
            }
            #判断卡密是否指定资源
            if (!empty($kammi['vid'])) {
                if ($kammi['vid'] != $arr['vid']) {
                    return [false, '卡密错误，此卡密不是本资源卡密'];
                }
            } else {
                if ($arr['money'] != $kammi['money']) {
                    return [false, '卡密价格错误'];
                }
            }
            #写入订单数据
            $arr['trade_id'] = $arr['cdkey'];
            $arr['status'] = 1;
            $arr['pay_type'] = 3;
            $order = Order::create($arr, true);
            if (!$order) {
                return [false, '购买失败'];
            }
            switch ($type) {
                case 1:#购买资源后处理
                    #todo 卡密多次购买
                    $work_id = 0;
                    if ($arr['spread_type'] == 6) {
                        $kam = ResourceKammi::where(['rid' => $arr['r_id'], 'uid' => 0])->find();
                        if (empty($kam)) {
                            $this->error('卡密库存不足，购买失败');
                        }
                        $kam->uid = $order->uid;
                        $kam->utime = time();
                        $kamres = $kam->save();
                        if (!$kamres) {
                            $this->error('数据异常，购买失败');
                        }
                        $work_id = $kam->id;
                    }
                    $arr1 = [
                        'rid' => $order->rid,
                        'uid' => $order->uid,
                        'work_id' => $work_id,
                        'type' => 1,
                        'admin_id' => $order->admin_id
                    ];
                    $res1 = UserResource::create($arr1);
                    if (!$res1) {
                        return [false, '操作失败，用户使用写入失败'];
                    }
                    break;
                case 2:#购买VIP后处理
                    $user = \app\common\model\User::where('id', $order->uid)->find();
                    if (!$user) {
                        return [false, '操作失败，用户不存在'];
                    }
                    $vip = Svip::where('id', $order->vid)->find();
                    if (!$vip) {
                        return [false, '操作失败，SVIP等级不存在'];
                    }
                    $user->vid = $order->vid;
                    $exp_time = $user->exp_time;
                    if ($exp_time < time()) {
                        $exp_time = time();
                    }
                    $user->exp_time = $exp_time + ($vip->days * 86400);
                    if (!$user->save()) {
                        return [false, '操作失败，处理SVIP失败'];
                    }
                    break;
            }
            #更新卡密使用状态
            $kammi->uid = $order->uid;
            $kammi->status = 1;
            $kammi->utime = time();
            if (!$kammi->save()) {
                return [false, '操作失败，更新失败'];
            }
            #代理收益
            list($res, $info) = $this->agent($order->ordno);
            if (!$res) {
                return [false, $info];
            }
            return [true, 'success'];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 代理收益
     * @return [type] [description]
     */
    protected function agent($ordno)
    {
        #============================计算代理收益================================================
        $order = Order::where('ordno', $ordno)->find();
        //计算用户分销
        if ($order->hr_money > 0 && $order->pid > 0) {
            list($res, $info) = Bill::money(1, $order->type, $order->hr_money, $order->pid, '推广佣金', $order->id);
            if (!$res) {
                return [false, $info];
            }
        }
        //代理提成
        $is_kl = 0;
        $agent = Admin::where('id', $order->admin_id)->find();//扣量判断
        $admin_id = $order->admin_id;
        if ($agent->admin_id > 0) {
            #扣量比例
            $take_num = $agent->take_num;
            if ($take_num > 0) {
                $count = Order::where(['uid' => $order->admin_id, 'status' => 1])->count();
                if ($count > 0 && ($count + 1) % $take_num == 0) {
                    $is_kl = 1;
                }
            }
        }
        //优化逻辑扣量
        if ($is_kl == 1) {
            //扣量逻辑
            if ($agent->admin_id == 0) {
                $admin_id = $agent->id;
            } else {
                $admin_id = (new Admin())->getDefaultAdminId();//todo 获取总代理即管理员ID
            }
        }

        //计算提成
        $min_take = $agent->min_take;
        $take_money = 0;
        #剩余金额
        $money = bcsub($order->money, $order->hr_money, 2);

        #上级代理分成
        if ($min_take > 0 && $is_kl == 0 && $agent->admin_id > 0) {
            $take_money = bcdiv(bcmul($order->money, $min_take, 2), 100, 2);
            if ($take_money) {
                $money = bcsub($money, $take_money, 2);
            }
        }
        #收益处理
        if ($money > 0 && $admin_id > 0) {
            #扣量处理
            if ($is_kl == 1) {
                $remark = "【扣量订单】单号:{$order->ordno} 代理ID:" . $order->admin_id . " 代理名称:" . $agent->username;
                $type = 4;
            } else {
                if ($order->type == 1) {
                    $remark = '【资源销售】单号:' . $order->ordno;
                    $type = 1;
                } else {
                    $remark = '【VIP销售】单号:' . $order->ordno;
                    $type = 3;
                }
            }
            list($res, $info) = AdminBill::money(1, $type, $money, $admin_id, $remark, $order->id);
            if (!$res) {
                return [false, $info];
            }
        }
        #提成
        if ($take_money && $is_kl == 0 && $agent->admin_id > 0) {
            $remark = "【分销抽成】单号:{$order->ordno};提成抽取比例{$agent->min_take}%;代理【{$agent->username}】ID:{$agent->id}";
            list($res, $info) = AdminBill::money(1, 3, $take_money, $agent->admin_id, $remark, $order->id);
            if (!$res) {
                Db::rollback();
                return [false, $info];
            }
        }
        $order->pt_money = $money;
        $order->tc_money = $take_money;
        $order->is_kl = $is_kl;
        $order->status = 1;
        $res = $order->save(); // 保存订单
        if (!$res) {
            return [false, '订单更新失败'];
        }
        return [true, 'success'];
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
     * 检查是否登录
     * @return mixed
     */
    public function checkLogin()
    {
        $user = cookie('user');
        if (empty($user)) {
            $url = urlencode($this->request->param('url'));
            echo ' <a class="user-login" href="/login?fromurl=' . $url . '" rel="nofollow"><i class="icon icon-user"></i>登录/注册</a>';
            exit;
        } else {
            $user = \app\common\model\User::where('id', $user['id'])->find();
            $expDate = $user->exp_time ? date('Y-m-d', $user->exp_time) : '长期有效';
            $SipName = Svip::getSvipName($user->vid, $user->exp_time);
            if($user->vid>0){
                $SipName='<span class="svip-name">'.$SipName.'</span>';
            }else{
                $SipName='<span class="normal-name">'.$SipName.'</span>';
            }
            $str = '<div id="ZpMenu">
                    <a href="/user" class="link">
                        <img src="' . $user['avatar'] . '" title="workrd" width="40" height="40" class="hy-tx">
                        <span class="nickname">' . $user['nickname'] . '</span>
                    </a>
                    <!-- 我是隐藏显示 -->
                    <script>
                        $(\'#ZpMenu\').hover(function () {
                            $(\'#ZpMenu2\').show();
                        },function () {
                            $(\'#ZpMenu2\').hide();
                        });
                    </script>
                    <div id="ZpMenu2">
                        <i class="arrow"></i>
                        <!-- 我是父 -->
                        <div class="nav-fu">
                            <!-- 我是子 -->
                            <div class="nav-zi">
                                <!-- 用户头像 -->
                                <div class="img">
                                    <a href="/user" class="avat"><img src="' . $user['avatar'] . '"></a>
                                </div>
                                <!-- 用户头像 End -->
                                <!-- 用户帐号 -->
                                <div class="ut">
                                    <div class="ut-span">
                                        <a href="/user" title="' . $user['nickname'] . '">' . $user['nickname'] . '</a>
                                    </div>
                                    <!-- 用户权限 -->
                                    <div class="user-vip">' . $SipName . '
                                        <span class="svip-date">' . $expDate . '</span>
                                    </div>
                                    <!-- 用户权限 End -->
                                </div>
                                <!-- 用户帐号 End -->
                            </div>
                            <!-- 我是子 End -->
                            <!-- 用户导航 -->
                            <div class="u-nav">
                                <ul class="ulist">
                                    <li>
                                        <a href="/user/mycourse">
                                            <i class="icon icon-ziyuan2"></i>
                                            <p class="name">我的资源</p>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/user/mysvip">
                                            <i class="icon icon-icon"></i>
                                            <p class="name">会员中心</p>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/user">
                                            <i class="icon icon-user"></i>
                                            <p class="name">个人中心</p>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <!-- 用户导航 End -->
                            <!-- 退出帐号 -->
                            <div class="zp-hui" style="overflow:hidden;padding:0 38px;">
                                <a href="javascript:;" onclick="WCCE.loginOut();" class="login-out">退出登录</a>
                            </div>
                            <!-- 退出帐号 End -->
                        </div>
                        <!-- 我是父 End -->
                    </div>
                    <!-- 我是隐藏显示 End -->
                </div>';
            echo $str;
            exit;
        }
    }

    /**
     * 退出登录
     */
    public function loginOut()
    {
        if ($this->request->isPost()) {
            cookie('user', null);
            $this->success('success');
        }
    }

    /**
     * 登录页面
     * @return mixed
     */
    public function login()
    {
        if ($this->request->isAjax()) {
            try {
                $data = $this->request->post();
                $validate = new Check();
                if (!$validate->scene('Login.weblogin')->check($data)) {
                    $this->error($validate->getError());
                }
                $user = \app\common\model\User::where('username', $data['username'])->find();
                if (!$user) {
                    $this->error('登录账号输入错误');
                }
                $pwd = md5($data['pwd'] . $user->salt);
                if ($user->pwd !== $pwd) {
                    $this->error('登录密码输入错误');
                }
                if ($user->status == 0) {
                    $this->error('登录账号已禁用');
                }
                $user->token = md5(time() . mt_rand(1000, 9999));
                $user->utime = time();
                $user->save();
                unset($user['pwd']);
                unset($user['salt']);
                $is_checked = $data['is_checked'];
                $times = $is_checked ? 3600 * 24 * 7 : 3600 * 24;
                cookie('user', $user->toArray(), $times);
                $this->success('success');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('title', '用户登录 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/login');
    }

    /**
     * 短信验证码登录
     */

    /**
     *微信扫码登录
     */
    public function loginQrcode()
    {
        try {
            if ($this->request->isAjax()) {
                $now_time = time();
                $ip = $this->request->ip(0, true);
                $key = md5($ip . $now_time);
                $qrcode = new UserQrcode();
                $user_qrcode = [
                    'ctime' => $now_time,
                    'key' => $key,
                    'from_id' => empty(cookie('site_id')) ? (new Admin())->getDefaultAdminId() : cookie('site_id'),
                    'rid' => empty(cookie('r_id')) ? 0 : cookie('r_id'),
                    'invite_uid' => empty(cookie('invite_uid')) ? 0 : cookie('invite_uid'),
                ];
                $userQrcode = $qrcode::create($user_qrcode, true);
                #获取登录二维码
                $app = Facade::officialAccount();
                $exp_time = 240;
                $result = $app->qrcode->temporary($key, $exp_time);
                $userQrcode->ticket = $result['ticket'];
                $res = $userQrcode->save();
                if (!$res) {
                    $this->error('获取登录二维码失败');
                }
                $result['url'] = $app->qrcode->url($result['ticket']);
                $result['key'] = $key;
                $result['exp_time'] = $userQrcode->ctime + $exp_time;
                $this->success('success', '', $result);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } catch (\EasyWeChat\Kernel\Exceptions\HttpException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 扫码登录查询
     */
    public function scanQrcode()
    {
        try {
            if ($this->request->isAjax()) {
                $uQrcode = new UserQrcode();
                $key = $this->request->param('key/s');
                $ticket = $this->request->param('ticket/s');
                $expire_seconds = $this->request->param('expire_seconds/d');
                $exp_time = $this->request->param('exp_time/d');
                if ($exp_time < time()) {
                    $uQrcode->where(['key' => $key, 'ticket' => $ticket])->delete();
                    $this->result([], 3, '扫码失败');
                }
                $userQrcode = $uQrcode->where(['key' => $key, 'ticket' => $ticket])->find();
                if (empty($userQrcode)) {
                    $this->result([], 4, '扫码失败');
                }
                if ($userQrcode->uid == 0) {
                    $this->error('还未扫码');
                } else {
                    #已经扫码后执行登录操作
                    $user = \app\common\model\User::where(['id' => $userQrcode->uid, 'status' => 1])->find();
                    if (!$user) {
                        $this->result([], 5, '登录失败');
                    }
                    $user->token = md5(time() . mt_rand(1000, 9999));
                    $user->utime = time();
                    $user->save();
                    $uQrcode->where(['id' => $userQrcode->id])->delete();
                    unset($user['pwd']);
                    unset($user['salt']);
                    $times = 3600 * 24;
                    cookie('user', $user->toArray(), $times);
                    $this->success('success');
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 注册
     */
    public function register()
    {
        try {
            if ($this->request->isAjax()) {
                $data = $this->request->param();
                $validate = new Check;
                if (!$validate->scene('Login.register')->check($data)) {
                    $this->error($validate->getError());
                }
                $user = \app\common\model\User::where('username|mobile', '=', $data['mobile'])->find();
                if ($user) {
                    $this->error('手机号码已注册过了');
                }
                $code = Validate::where(['mobile' => $data['mobile'], 'status' => 0])->order('id desc')->find();
                if (!$code) {
                    $this->error('验证码错误');
                }
                if ((time() - 5 * 60) > strtotime($code->ctime)) {
                    $this->error('验证码已过期');
                }
                if ($code->code != $data['code']) {
                    $this->error('验证码输入错误');
                }
                $code->status = 1;
                if (!$code->save()) {
                    $this->error('注册失败');
                }
                $arr['username'] = $data['mobile'];
                $arr['mobile'] = $data['mobile'];
                $arr['salt'] = mt_rand(100001, 999999);
                $arr['pwd'] = md5($data['pwd'] . $arr['salt']);
                $arr['admin_id'] = empty($data['from']) ? (new Admin())->getDefaultAdminId() : $data['from'];
                $arr['pid'] = empty($data['invite_uid']) ? 0 : $data['invite_uid'];
                $arr['token'] = md5(time() . mt_rand(1000, 9999));
                $arr['avatar'] = config('setting.web_logo');
                $arr['nickname'] = '用户' . rand_string(8,0);
                $info = \app\common\model\User::create($arr, true);
                if (!$info) {
                    $this->error('注册失败1');
                }
                if (!empty($data['rid'])) {
                    #写入助力记录
                    if (!empty($data['invite_uid'])) {
                        $invite = [
                            'admin_id' => $data['from'],
                            'rid' => $data['rid'],
                            'pid' => $data['invite_uid'],
                            'uid' => $info->id,
                            'ctime' => time()
                        ];
                        \app\common\model\Invite::create($invite, true);
                        #更新资源邀请任务信息
                        $task = ResourceTask::where('uid', $data['invite_uid'])->where('rid', $data['rid'])->find();
                        if ($task) {
                            $spread = Spread::where('id', $data['rid'])->find();
                            if ($task->invite_num >= $spread->invite_num && $task->is_video == 1) {
                                $task->status = 1;
                            }
                            $task->invite_num = ['inc', 1];
                            $task->utime = time();
                            $task->save();
                        }
                    }
                }
                unset($info['pwd']);
                unset($info['salt']);
                cookie('user', $info->toArray(), 3600 * 24);
                cookie('invite_uid', 0);
                cookie('r_id', 0);
                $this->success('success');
            }
            $this->error('注册失败');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 找回密码
     * @return mixed
     */
    public function findme()
    {
        if ($this->request->isAjax()) {
            try {
                $data = input('param.');
                $validate = new Check;
                if (!$validate->scene('Login.resetpwd')->check($data)) {
                    $this->error($validate->getError());
                }
                if ($data['pwd'] != $data['rpwd']) {
                    $this->error('二次密码输入不一致');
                }
                $user = \app\common\model\User::where('username', $data['mobile'])->find();
                if (!$user) {
                    $this->error('账号不存在');
                }
                $code = Validate::where('mobile', $data['mobile'])->order('id desc')->where('status', 0)->find();
                if (!$code) {
                    $this->error('验证码错误');
                }
                if ((time() - 5 * 60) > strtotime($code->ctime)) {
                    $this->error('验证码已过期');
                }
                if ($code->code != $data['code']) {
                    $this->error('验证码错误');
                }
                $code->status = 1;
                if (!$code->save()) {
                    $this->error('操作失败');
                }
                $user->pwd = md5($data['pwd'] . $user->salt);
                $user->utime = time();
                if (!$user->save()) {
                    $this->error('操作失败1');
                }
                $this->success('success');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('title', '找回密码 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/findme');
    }

    /**
     * 检测是否登录
     * @return mixed
     */
    protected function checkUserLogin()
    {
        $user = cookie('user');
        if (empty($user)) {
            header('Location:/login?fromurl=' . urlencode($this->request->url(true)));
            exit;
        }
    }

    /**
     * 会员中心
     * @return mixed
     */
    public function user()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/user_center');
    }

    /**
     * 会员中心个人信息
     * @return mixed
     */
    public function myinfo()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/myinfo');
    }

    /**
     * 会员中心推广
     * @return mixed
     */
    public function spread()
    {
        $this->checkUserLogin();
        $user = cookie('user');
        $invite_url = $this->request->domain() . '/u_' . $user['id'];
        $this->assign('invite_url', $invite_url);
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/spread');
    }

    /**
     * 会员中心我的钱包
     * @return mixed
     */
    public function cash()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/cash');
    }

    /**
     * 会员中心提现列表
     * @return mixed
     */
    public function cashlist()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/cashlist');
    }

    /**
     * 会员中心修改密码
     * @return mixed
     */
    public function pwd()
    {
        $this->checkUserLogin();
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            if (empty($data['opwd']) || strlen($data['opwd']) < 6 || strlen($data['opwd']) > 20) {
                $this->error('请输入原密码');
            }
            if (empty($data['npwd']) || strlen($data['npwd']) < 6 || strlen($data['npwd']) > 20) {
                $this->error('请输入新密码');
            }
            if (empty($data['epwd']) || strlen($data['epwd']) < 6 || strlen($data['epwd']) > 20) {
                $this->error('请输入二次输入新密码');
            }
            if ($data['npwd'] !== $data['epwd']) {
                $this->error('二次密码输入错误');
            }
            $salt = mt_rand(111111, 999999);
            $data['npwd'] = md5($data['npwd'] . $salt);
            $user_info = cookie('user');
            $user = \app\common\model\User::where(['id' => $user_info['id'], 'status' => 1])->find();
            if (empty($user)) {
                $this->error('用户不存在或账号被禁用');
            }
            $oldpwd = md5($data['opwd'] . $user->salt);
            if ($user->pwd !== $oldpwd) {
                $this->error('原密码输入错误');
            }
            $user->pwd = $data['npwd'];
            $user->salt = $salt;
            $user->utime = time();
            $result = $user->save();
            if (!$result) {
                $this->error('密码修改失败');
            }
            $this->success('密码修改成功');
        }
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/pwd');
    }

    /**
     * 会员中心我收藏
     * @return mixed
     */
    public function myfav()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/myfav');
    }

    /**
     * 会员中心我的VIP
     * @return mixed
     */
    public function mysvip()
    {
        $this->checkUserLogin();
        $field = 'id,name,days,price,content,invite_num';
        $svip_list = Svip::field($field)->where(['type' => 0, 'status' => 1,'admin_id'=>$this->admin_id])->order('level asc')->select();
        foreach ($svip_list as $k => $v) {
            $svip_list[$k]['days'] = $v['days'] . '天';
            if ($v['days'] >= 9999) {
                $svip_list[$k]['days'] = '永久';
            }
        }
        $total = 0;
        $rid = 0;
        if (count($svip_list) > 0) {
            $total = $svip_list[0]['price'];
            $rid = $svip_list[0]['id'];
        }
        $this->assign('rid', $rid);
        $this->assign('total', $total);
        $this->assign('svip_list', $svip_list);
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/mysvip');
    }

    /**
     * 会员中心我的资源
     * @return mixed
     */
    public function mycourse()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/mycourse');
    }

    /**
     * 提现记录
     * @return mixed
     */
    public function record()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/record');
    }

    /**
     * 推广主页
     * @return mixed
     */
    public function referrer()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/referrer');
    }

    /**
     * 收益明细
     * @return mixed
     */
    public function income()
    {
        $this->checkUserLogin();
        $this->assign('title', '用户中心 - ' . config('setting.coms_title'));
        $this->assign('keyword', config('setting.coms_keys'));
        $this->assign('desc', config('setting.coms_desc'));
        return $this->tpl_fetch('/income');
    }

}