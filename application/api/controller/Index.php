<?php

namespace app\api\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\AdminSite;
use app\common\model\Adv;
use app\common\model\PosterConfig;
use app\common\model\Privilege;
use app\common\model\Spread;
use Fastknife\Exception\ParamException;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use app\common\model\{Swiper, Article};
use app\api\validate\Check;
use app\common\model\Theme;
use app\common\model\ThemeConfig;
use app\common\model\ThemeItems;
use app\common\model\User;
use EasyWeChat\Kernel\Exceptions\HttpException;
use Naixiaoxin\ThinkWechat\Facade;
use oss\Alioss;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use app\common\model\Validate;
use oss\Qcloud;
use PosterMaker\PosterMaker;
use think\Exception;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use EasyWeChat\Kernel\Support\File;
use app\common\model\Qiniu;

/**
 *
 */
class Index extends Common
{
    /**
     * 获取广告配置
     * @return array|void
     */
    public function advert()
    {
        $page_id = $this->request->param('page_id/d', 0);
        $type = $this->request->param('type/d', 0);
        $sort = $this->request->param('sort/d', 0);
        $from_id = $this->request->param('from_id');
        $token = $this->request->param('token');
        $agent = Admin::where('id', $from_id)->find();
        //代理开启广告
        if ($agent->is_ad == 1) {
            #查询会员特权
            if ($token) {
                $user = \app\common\model\User::where('token', $token)->find();
                if ($user && $user->exp_time > time()) {
                    $is_ad = Privilege::handleUserAuth($user->vid, '', 4);
                    if ($is_ad) {
                        $this->error('未开启广告');
                    }
                }
            }
            #查询广告
            $advert = Adv::alias('a')
                ->join('adv_set b', 'a.id=b.aid')
                ->where('b.adsense', $page_id)
                ->where('b.type', $type)
                ->where('a.sort', $sort)
                ->field('a.id,a.name,a.advid,a.sort')
                ->find();
            $this->success('success', $advert);
        }
        $this->error('未开启广告');
    }

    /*
     * 获取系统配置
     */
    public function config()
    {
        $data = [
            'web' => [
                'name' => config('setting.web_name'),
                'logo' => config('setting.web_logo'),
                'qrcode' => config('setting.web_qrcode'),
                'wechat' => config('setting.web_wechat'),
                'phone' => config('setting.web_phone'),
                'contact' => config('setting.web_contact'),
                'url' => config('setting.account_domain'),
                'view_type' => config('setting.view_type'),//访问方式
                'view_nosite'=> config('setting.view_nosite'),//无分站域名默认跳转地址
            ],
            'subscribe' => [
                'cash_id' => config('setting.subscribe_cashed_id'),
                'new_id' => config('setting.subscribe_new_id'),
                'task_id' => config('setting.subscribe_task_id')
            ],
            'share' => [
                'title' => config('setting.wechat_share_title'),
                'desc' => config('setting.wechat_share_desc'),
                'img' => config('setting.wechat_share_img')
            ],
            'cash' => [
                'video_ids' => config('setting.video_ids'),//激励视频ID
                'try_see' => config('setting.try_see'),//视频试看时间
                'cash_fee' => config('setting.cash_fee'),//提现手续费
                'cash_iswechat' => config('setting.cash_iswechat'),//微信提现
                'cash_isalipay' => config('setting.cash_isalipay'),//支付宝提现,
                'cash_isbank' => config('setting.cash_isbank'),//支付宝提现,
                'cash_min' => config('setting.cash_min'),//最低提现金额
                'is_uptime' => config('setting.is_uptime'),//是否显示更新日期,
                'hand_day_max' => config('setting.hand_day_max'),//每日助力限制
                'hand_day_num' => config('setting.hand_day_num'),//每日资源获取次数
                'ios_close' => config('setting.ios_close'),//是否关闭IOS虚拟支付
                'ios_type' => config('setting.ios_type'),//ios支付开启辅助方式
                'is_opendisk' => config('setting.is_opendisk'),//是否显示打开网盘
                'is_groups' => config('setting.is_groups'),//开放站群加入
                'is_jumps' => config('setting.is_jumps'),//开放跳转加入
                'list_type' => config('setting.list_type'),//资源列表排列方式
                'is_cdkey_pay' => config('setting.is_cdkey_pay'),//是否开启安卓卡密支付
                'vip_article_id' => config('setting.vip_article_id'),//自定义VIP介绍文章ID
                'sale_unit_text' => config('setting.sale_unit_text'),//自定义销量文字
                'service_artice_id' => config('setting.service_artice_id'),//自定义用户注册服务文章ID
                'privacy_artice_id' => config('setting.privacy_artice_id'),//自定义用户隐私协议文章ID
                'resource_name' => config('setting.resource_name'),//资源页面自定义名称
                'vip_cdkey_pay' => config('setting.vip_cdkey_pay'),//安卓端会员是否开启卡密支付
                'is_wallet_person' => config('setting.is_wallet_person'),//是否显示我的钱袋菜单
                'is_bind_mobile' => config('setting.is_bind_mobile'),//是否显示我的钱袋菜单
                'is_wechat_pay' => config('setting.is_wechat_pay'),//是否显示微信支付
                'is_quick_login' => config('setting.is_quick_login'),//是否开启一键快捷登录
                'kefu_link' => config('setting.kefu_link'),//H5端平台客服投诉链接
                'course_type' => config('setting.course_type'),//视频课程默认是课程在前或者目录在前
                'is_wechat_login' => config('setting.is_wechat_login'),//微信登录显示方式
                'is_passed_mode' => config('setting.is_passed_mode'),//过审模式
            ],
            'wechat' => [
                'appid' => config('setting.account_appid'),//微信公众号
                'auth_url' => config('setting.account_domain'),//微信网页授权域名url
                'wechat_ptype' => config('setting.wechat_pay_type'),//公众号微信支付方式
                'wxmini_ptype' => config('setting.wxnini_pay_type'),//小程序微信支付方式
                'wechat_open' => config('setting.wechat_open'),//是否开启微信支付
                'root_domains' => explode("\n", trim(config('setting.account_domains'))),//代理分站顶级域名库
            ],
            'alipay' => [
                'alipay_ptype' => config('setting.alipay_pay_type'),//支付宝支付方式
                'alipay_open' => config('setting.alipay_open'),//是否开启支付宝支付
            ],
            'toutiao' => [
                'video_ids' => config('setting.tt_video_ids'),//激励视频ID
                'is_video' => config('setting.tt_isvideo'),//是否开启拍视频
            ]
        ];
        #获取代理联系方式
        $admin_id = $this->request->param('from_id');
        #获取分站配置
        $siteModel = new AdminSite();
        $sub_domin = $this->request->param('site');
        $data['web']['nosite'] = 0;
        if (!empty($sub_domin)) {
            $url = str_replace(['https://', 'http://'], ['', ''], config('setting.account_domain'));
            if ($url !== $sub_domin && !in_array($sub_domin, ['localhost', '127.0.0.1'])) {
                $admin_id = $siteModel->getSubAdminId($sub_domin);
                if($admin_id==1){
                    $data['web']['nosite'] = 1;
                }
            }
        }
        $data['web']['webid'] = $admin_id;
        $agent = Admin::where('id', $admin_id)->find();
        if (!empty($agent)) {
            if (!empty($agent->webname)) {
                $data['web']['name'] = $agent->webname;
            }
            if (!empty($agent->realname)) {
                $data['web']['contact'] = $agent->realname;
            }
            if (!empty($agent->qrcode)) {
                $data['web']['qrcode'] = $agent->qrcode;
            }
            if (!empty($agent->wechat)) {
                $data['web']['wechat'] = $agent->wechat;
            }
            if (!empty($agent->mobile)) {
                $data['web']['phone'] = $agent->mobile;
            }
            $site = $siteModel->where('admin_id', $admin_id)->find();
            if (!empty($site)) {
                $data['web']['name'] = $site->webname;
                $data['web']['contact'] = $site->realname;
                $data['web']['qrcode'] = $site->qrcode;
                $data['web']['wechat'] = $site->wechat;
                $data['web']['phone'] = $site->mobile;
            }
            $data['web']['url'] = $siteModel->getSiteUrl($admin_id);
        }
        #获取主题配色
        $data['theme_config'] = '';
        $theme_config = ThemeConfig::where(['admin_id' => $admin_id, 'page' => 0])->value('params');
        if (!empty($theme_config)) {
            $data['theme_config'] = json_decode($theme_config, true);
        }else{
            $adminId = (new Admin())->getDefaultAdminId();
            $theme_config = ThemeConfig::where(['admin_id' => $adminId, 'page' => 0])->value('params');
            if(!empty($theme_config)){
                $data['theme_config'] = json_decode($theme_config, true);
            }
        }
        #tabbar配置
        $data['tabbar'] = null;
        list($res, $tabBar) = $this->widgetData($admin_id);
        if ($res) {
            $data['tabbar'] = $tabBar;
        }
        $this->success('success', $data);
    }

    /**
     * 获取tabbar配置数据
     * @return array
     */
    protected function widgetData($admin_id)
    {
        #获取首页布局配置
        $view_id = Admin::where('id', $admin_id)->value('view_id');
        $where = ['id' => $view_id];
        if (empty($view_id)) {
            $where = ['admin_id' => $admin_id];
        }
        $itemModel = new ThemeItems();
        $page_code = Theme::where($where)->order('id asc')->value('code');
        if (!empty($page_code)) {
            list($result,$itemPage)=$itemModel->getWidget($page_code, $admin_id);
            if(empty($result)){
                return [false, $itemPage];
            }
            return [true, $itemPage];
        } else {
            #默认布局ID=1
            $admin_id = (new Admin())->getDefaultAdminId();
            $view_id = Admin::where('id', $admin_id)->value('view_id')??1;
            $page_code = Theme::where(['id' => $view_id, 'type' => 1, 'layout' => 1])->order('id asc')->value('code');
            list($result,$itemPage)=$itemModel->getWidget($page_code, $admin_id);
            if(empty($result)){
                return [false, $itemPage];
            }
            return [true, $itemPage];
        }
    }

    /**
     * 获取首页DIY配置数据
     */
    public function pageData()
    {
        $admin_id = $this->request->param('from_id');
        #获取首页布局配置
        $view_id = Admin::where('id', $admin_id)->value('view_id');
        $where = ['id' => $view_id];
        if (empty($view_id)) {
            $where = ['admin_id' => $admin_id];
        }
        $page_code = Theme::where($where)->value('code');
        if (!empty($page_code)) {
            $itemModel = new ThemeItems();
            $result = $itemModel->getParams($page_code, $admin_id);
            $pageConfig = [];
            if ($result['data']) {
                foreach ($result['data']['items'] as $key => $value) {
                    $pageConfig[$key]['type'] = $value['widget_code'];
                    $pageConfig[$key]['params'] = $value['params'];
                }
            }
            $data = $pageConfig;
        } else {
            #默认布局ID=1
            $page_code = Theme::where('id', 1)->value('code');
            $itemModel = new ThemeItems();
            $admin_id = (new Admin())->getDefaultAdminId();
            $result = $itemModel->getParams($page_code, $admin_id);
            $pageConfig = [];
            if ($result['data']) {
                foreach ($result['data']['items'] as $key => $value) {
                    $pageConfig[$key]['type'] = $value['widget_code'];
                    $pageConfig[$key]['params'] = $value['params'];
                }
            }
            $data = $pageConfig;
        }
        $this->success('success', $data);
    }

    /**
     * 获取自定义页面配置数据
     */
    public function customData()
    {
        $custom_id = $this->request->param('id/d', 0);
        $custom_page = Theme::where('id', $custom_id)->find();
        if (empty($custom_page)) {
            $this->error('自定义页面不存在');
        }
        $itemModel = new ThemeItems();
        $result = $itemModel->getParams($custom_page->code, $custom_page->admin_id);
        $customConfig = [];
        if ($result['data']) {
            foreach ($result['data']['items'] as $key => $value) {
                $customConfig[$key]['type'] = $value['widget_code'];
                $customConfig[$key]['params'] = $value['params'];
            }
        }
        $data = [
            'page_name' => $custom_page->name,
            'data' => $customConfig
        ];
        $this->success('success', $data);
    }

    /**
     *获取自定义配置项
     */
    public function getThemeConfig()
    {
        $admin_id = $this->request->param('from_id');
        $type = $this->request->param('type/d', 0);
        $config = ThemeConfig::where(['page'=>$type,'admin_id'=>$admin_id])->find();
        if (!empty($config)) {
            $theme = json_decode($config->params, true);
        }else{
            $admin_id = (new Admin())->getDefaultAdminId();
            $config = ThemeConfig::where(['page'=>$type,'admin_id'=>$admin_id])->find();
            $theme = json_decode($config->params, true);
        }
        $this->success('success', $theme);
    }

    /**
     * 轮播图
     * @return [type] [description]
     */
    public function swiper()
    {
        $list = (new Swiper)->field('title,url,link')
            ->where('status', 1)
            ->where('type', 0)
            ->select();
        $this->success('success', ['list' => $list]);
    }

    /**
     * 资讯公告
     */
    public function article()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Index.article')->check($data)) {
                $this->error($validate->getError());
            }
            $list = (new Article)->field('id,title,ctime,thumb,content')
                ->where('sort_id', $data['sort_id'])
                ->where('status', 1)
                ->select();
            $this->success('success', ['list' => $list]);
        }
    }

    /**
     * 资讯详情
     * @return [type] [description]
     */
    public function articledetail()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Index.articledetail')->check($data)) {
                $this->error($validate->getError());
            }
            $info = (new Article)->field('title,ctime,thumb,content')
                ->where('id', $data['id'])
                ->where('status', 1)
                ->find();
            $this->success('success', $info);
        }
    }

    /**
     * 获取字节抖音小程序码
     * @return [type] [description]
     */
    public function gettcode()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Index.getxcode')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            if (empty($data['fid'])) {
                $data['fid'] = 1;
            }
            $scene = $data['id'] . '_' . $data['type'] . '_' . $uid . '_' . $data['fid'] . '_' . $data['r_type'];
            $path = $data['path'] . '?scene=' . $scene;
            $toutiao = new Toutiao();
            list($result, $content) = $toutiao->getQrcode(['path' => urlencode($path)]);
            if ($result) {
                $storage_type = config('setting.upload_storage');
                if ($storage_type == 'local') {
                    list($res, $filename) = $this->savePoster($content, 'uploads/qrcode', 't_' . $uid . $data['id'], true);
                    if (!$res) {
                        $this->error('二维码生成失败');
                    }
                    $this->success('success', ['imgurl' => $this->request->domain() . '/uploads/qrcode/' . $filename]);
                } elseif ($storage_type == 'aliyun') {
                    $filename = 't_' . $uid . '_' . $data['id'] . '.png';
                    $oss = new Alioss();
                    $result = $oss->pudata($filename, $content, 'uploads/qrcode');
                    if ($result['status'] == 200) {
                        $this->success('success', ['imgurl' => $result['data']['url']]);
                    } else {
                        $this->error($result['msg']);
                    }
                } elseif ($storage_type == 'qiniu') {
                    $filename = 't_' . $uid . '_' . $data['id'] . '.png';
                    $qiniu = new Qiniu;
                    $result = $qiniu->uploadData($content, $filename);
                    if ($result['status'] == 200) {
                        $this->success('success', ['imgurl' => $result['data']['url']]);
                    } else {
                        $this->error('生成失败');
                    }
                }
            } else {
                $this->error('您的小程序还未发布');
            }
        }
    }

    /**
     * 获取微信小程序码
     * @return [type] [description]
     */
    public function getxcode()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('Index.getxcode')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $app = Facade::miniProgram();
            if (empty($data['fid'])) {
                $data['fid'] = 1;
            }
            $scene = $data['id'] . '_' . $data['type'] . '_' . $uid . '_' . $data['fid'] . '_' . $data['r_type'];
            $optional['page'] = $data['path'];
            $response = $app->app_code->getUnlimit($scene, $optional);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $storage_type = config('setting.upload_storage');
                if ($storage_type == 'local') {
                    $filename = $response->saveAs('uploads/qrcode', $uid . $data['id'], true);
                    if (!$filename) {
                        $this->error('二维码生成失败');
                    }
                    $this->success('success', ['imgurl' => $this->request->domain() . '/uploads/qrcode/' . $filename]);
                } elseif ($storage_type == 'aliyun') {
                    $contents = $response->saveContent('uploads/qrcode', $uid . $data['id'], true);
                    $filename = $uid . '_' . $data['id'] . '.jpg';
                    $oss = new Alioss();
                    $result = $oss->pudata($filename, $contents, 'uploads/qrcode');
                    if ($result['status'] == 200) {
                        $this->success('success', ['imgurl' => $result['data']['url']]);
                    } else {
                        $this->error($result['msg']);
                    }
                } elseif ($storage_type == 'qiniu') {
                    $contents = $response->saveContent('uploads/qrcode', $uid . $data['id'], true);
                    $filename = $uid . '_' . $data['id'] . '.jpg';
                    $qiniu = new Qiniu;
                    $result = $qiniu->uploadData($contents, $filename);
                    if ($result['status'] == 200) {
                        $this->success('success', ['imgurl' => $result['data']['url']]);
                    } else {
                        $this->error('生成失败');
                    }
                }
            } else {
                $this->error('您的小程序还未发布');
            }
        }
    }

    /**
     * 发送短信验证码
     * @return [type] [description]
     */
    public function sendsms()
    {
        $data = input('param.');
        $validate = new Check;
        if (!$validate->scene('Index.sendsms')->check($data)) {
            $this->error($validate->getError());
        }
        #验证行为验证码
        try {
            $service = $this->getCaptchaService();
            $service->verificationByEncryptCode($data['skeyCode']);
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }
        $Ucode = Validate::where('mobile', $data['mobile'])->where('ctime', '>', time() - 120)->find();
        if ($Ucode) {
            $this->error('验证码已经发送');
        }
        $code = mt_rand(100001, 999999);
        $sms = Validate::create(['mobile' => $data['mobile'], 'code' => $code]);
        if (!$sms) {
            $this->error('验证码发送失败');
        }
        $sms_channel = config('setting.sms_channel');
        switch (intval($sms_channel)) {
            case 1:#阿里云短信
                $config = new Config([
                    // 您的AccessKey ID
                    "accessKeyId" => config('setting.sms_keyid'),
                    // 您的AccessKey Secret
                    "accessKeySecret" => config('setting.sms_secret')
                ]);
                // 访问的域名
                $config->endpoint = "dysmsapi.aliyuncs.com";
                $client = new Dysmsapi($config);
                $arr = [
                    'phoneNumbers' => $data['mobile'],
                    'templateCode' => config('setting.TemplateCode'),
                    'templateParam' => json_encode(['code' => $code]),
                    'signName' => config('setting.sms_sign')
                ];
                $sendSmsRequest = new SendSmsRequest($arr);
                $info = $client->sendSms($sendSmsRequest);
                $result = $info->toMap();
                if ($result['body']['Code'] !== 'OK') {
                    $this->error($result['body']['Message']);
                }
                $this->success('发送成功');
                break;
            case 2:#短信宝短信
                $status = [
                    "0" => "短信发送成功",
                    "-1" => "参数不全",
                    "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
                    "30" => "密码错误",
                    "40" => "账号不存在",
                    "41" => "余额不足",
                    "42" => "帐户已过期",
                    "43" => "IP地址限制",
                    "50" => "内容含有敏感词"
                ];
                $content = str_replace(['{code}', '{time}'], [$code, 5], config('setting.dxb_temp'));
                $api_url = config('setting.dxb_apiurl');
                $params = [
                    'u' => config('setting.dxb_user'),
                    'p' => config('setting.dxb_apikey'),
                    'm' => $data['mobile'],
                    'c' => $content
                ];
                $result = httpRequest($api_url, 'get', $params);
                if ($result != '0') {
                    $this->error($status[$result]);
                }
                $this->success('发送成功');
                break;
        }
        $this->error('验证码发送失败');
    }

    /**
     * 生成课程H5海报
     */
    public function createH5Poster()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.posterh5')->check($data)) {
                $this->error($validate->getError());
            }
            $path = 'uploads/poster/h5';
            $uid = $this->request->param('uid');
            $id = $data['id'];
            list($result, $qrcode) = $this->createQrcode($data['links'], $id, $uid);
            if (!$result) {
                $this->error($qrcode);
            }
            //生成二维码图片
            $filename = $uid . '_' . $data['id'] . '.png';
            $user = User::where('id', $uid)->field('id,nickname,avatar')->find();
            $info = Spread::where('id', $id)->find();
            #获取自定义海报数据
            $setting = [
                'bg_color' => 'rgb(254,167,0)',
                'main_text' => '给您分享了一个好资源，扫码完成任务可免费领取',
                'bg_main_color' => 'rgb(255,255,255)',
                'sub_text' => '免费好资源就等你来',
            ];
            $admin_id = $this->request->param('from_id/d', 1);
            $config = PosterConfig::where(['admin_id' => $admin_id, 'type' => 1])->find();
            if (!empty($config)) {
                $setting = json_decode($config->setting, true);
            }
            $setting['bg_color'] = coverToRGB($setting['bg_color']);
            $setting['bg_main_color'] = coverToRGB($setting['bg_main_color']);
            $width = 1080;
            $height = 1600;
            $poster = new PosterMaker($width, $height, $setting['bg_color']);
            $poster->addImg($user->avatar, [40, 40], [128, 128], 64);
            $poster->addText($user->nickname, 30, [220, 80], [255, 255, 255]);
            $poster->addText($setting['main_text'], 22, [220, 140], [255, 255, 255]);
            $poster->addBg(($width - 80), $height * 0.84, [40, $height * 0.13], $setting['bg_main_color'], 24);
            $poster->addImg($info->thumb, [80, ($height * 0.13) + 40], [$width - 160, $width - 240], 0);
            $poster->addText('¥ ' . $info->price, 38, [80, $height * 0.73], [251, 55, 55]);
            $poster->addText($info->title, 34, [80, $height * 0.78], [10, 10, 10], '', 0, 600);
            $poster->addText($setting['sub_text'], 30, [80, $height * 0.88], [100, 100, 100], '', 0, 650);
            $poster->addText($info->sales . '人已获取', 22, [80, $height * 0.93], [254, 167, 0]);
            $poster->addImg($qrcode, [($width - 340), $height * 0.78], [256, 256]);
            $content = $poster->render($filename, 1); // 保持为图片
            $storage_type = config('setting.upload_storage');
            if ($storage_type == 'local') {
                list($res, $info) = $this->savePoster($content, $path, $filename, true);
                if (!$res) {
                    $this->error($info);
                }
                $this->success('success', ['url' => $this->request->domain() . '/' . $path . '/' . $info]);
            } elseif ($storage_type == 'aliyun') {
                $oss = new Alioss();
                $result = $oss->pudata($uid . '_' . $data['id'] . '.png', $content, $path);
                $result['data']['url'] = str_replace('http://', 'https://', $result['data']['url']);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($uid . '_' . $data['id'] . '.png', $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $uid . '_' . $data['id'] . '.png');
                if ($result['status']==200) {
                    $this->success('success', ['url' => $result['data']['url']]);
                } else {
                    $this->error('生成失败');
                }
            }
        }
    }

    /**
     * 生成H5推广二维码
     * @param $text
     * @param $id
     * @param $uid
     * @return array
     */
    protected function createQrcode($text, $id, $uid)
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setWriterByName('png');
        $content = $qrCode->writeString();
        $path = 'uploads/h5/qrcode';
        $filename = 'qrcode_' . $uid . '_' . $id . '.png';
        $storage_type = config('setting.upload_storage');
        if ($storage_type == 'local') {
            list($res, $filename) = $this->savePoster($content, $path, $filename, true);
            if (!$res) {
                return [false, '保存失败'];
            }
            return [true, $this->request->domain() . '/' . $path . '/' . $filename];
        } elseif ($storage_type == 'aliyun') {
            $oss = new Alioss();
            $result = $oss->pudata($filename, $content, $path);
            if ($result['status'] !== 200) {
                return [false, $result['msg']];
            }
            return [true, $result['data']['url']];
        } elseif ($storage_type == 'qcloud') {
            $oss = new Qcloud();
            $result = $oss->pudata($filename, $content, $path);
            if ($result['status'] !== 200) {
                return [false, $result['msg']];
            }
            return [true, $result['data']['url']];
        } elseif ($storage_type == 'qiniu') {
            $qiniu = new Qiniu;
            $result = $qiniu->uploadData($content, $path . '/' . $filename);
            if ($result['status'] !== 200) {
                return [false, '生成失败'];
            }
            return [true, $result['data']['url']];
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
     * 生成微信分享参数
     */
    public function getWxConfig()
    {
        try {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                $app = Facade::officialAccount();
                $url = urldecode($data['url']);
                $app->jssdk->setUrl($url);
                $result = $app->jssdk->buildConfig(
                    [
                        'updateAppMessageShareData',
                        'updateTimelineShareData',
                        'chooseWXPay',
                        'chooseImage',
                        'previewImage',
                        'uploadImage'
                    ], false, false, false);
                $this->success('获取成功', $result);
            }
        } catch (HttpException $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
    /**
     * 行为验证码
     */
    public function ajcaptcha()
    {
        try {
            $service = $this->getCaptchaService();
            $data = $service->get();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('success',$data);
    }
    /**
     * 一次验证
     */
    public function checkcaptcha()
    {
        $data = request()->post();
        try {
            $validate = new Check;
            if (!$validate->scene('Sms.captcha')->check($data)) {
                $this->error($validate->getError());
            }
            $service = $this->getCaptchaService();
            $service->check($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('success');
    }

    /**
     * 二次验证
     */
    public function verification()
    {
        $data = request()->post();
        try {
            $validate = new Check;
            if (!$validate->scene('Sms.captcha')->check($data)) {
                $this->error($validate->getError());
            }
            $service = $this->getCaptchaService();
            $service->verification($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('success');
    }
    /**
     * 输出验证码相关数据
     * @return BlockPuzzleCaptchaService|ClickWordCaptchaService
     */
    protected function getCaptchaService()
    {
        $captchaType = request()->post('captchaType', 'blockPuzzle');
        $config = config('captcha.');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new ParamException('captchaType参数不正确！');
        }
        return $service;
    }
}