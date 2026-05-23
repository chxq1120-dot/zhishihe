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
                'name' => '芝士盒',
                'logo' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20241007/c514fd92769122adcbc96d94a919f00a.png',
                'qrcode' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20250524/40efd6c86c974a7031f7e0206891d105.jpg',
                'wechat' => '',
                'phone' => '028-60225332',
                'contact' => '',
                'url' => 'http://localhost:8080/',
                'view_type' => 0,
                'view_nosite' => '',
            ],
            'subscribe' => [
                'cash_id' => '',
                'new_id' => '',
                'task_id' => ''
            ],
            'share' => [
                'title' => '芝士盒为您提供各类知识付费资源可免费领取！',
                'desc' => '芝士盒为您提供副业项目、创业项目、技能、知识等资源',
                'img' => 'http://zpcms.oss-cn-beijing.aliyuncs.com/public/uploads/20210520/a4ad53ad225876bbec6d337c48730726.jpg'
            ],
            'cash' => [
                'video_ids' => '',
                'try_see' => 10,
                'cash_fee' => 0,
                'cash_iswechat' => 2,
                'cash_isalipay' => 1,
                'cash_isbank' => 1,
                'cash_min' => 1,
                'is_uptime' => 1,
                'hand_day_max' => 10,
                'hand_day_num' => 0,
                'ios_close' => 1,
                'ios_type' => 3,
                'is_opendisk' => 0,
                'is_groups' => 1,
                'is_jumps' => 1,
                'list_type' => 1,
                'is_cdkey_pay' => 1,
                'vip_article_id' => 11,
                'sale_unit_text' => '人学习',
                'service_artice_id' => 3,
                'privacy_artice_id' => 2,
                'resource_name' => '',
                'vip_cdkey_pay' => 0,
                'is_wallet_person' => 1,
                'is_bind_mobile' => 2,
                'is_wechat_pay' => 2,
                'is_quick_login' => 0,
                'kefu_link' => '',
                'course_type' => 0,
                'is_wechat_login' => 0,
                'is_passed_mode' => 0,
            ],
            'wechat' => [
                'appid' => '',
                'auth_url' => 'http://localhost:8080/',
                'wechat_ptype' => 1,
                'wxmini_ptype' => 1,
                'wechat_open' => 2,
                'root_domains' => [],
            ],
            'alipay' => [
                'alipay_ptype' => 2,
                'alipay_open' => 1,
            ],
            'toutiao' => [
                'video_ids' => '',
                'is_video' => 0,
            ]
        ];
        #获取代理联系方式
        $admin_id = $this->request->param('from_id');
        if (empty($admin_id)) {
            $admin_id = 1;
        }
        #获取分站配置
        $sub_domin = $this->request->param('site');
        $data['web']['nosite'] = 0;
        $data['web']['webid'] = $admin_id;
        
        #获取主题配色
        $data['theme_config'] = [
            'themeColor' => '#FF9800',
            'themeColorLight' => '#FFE0B2',
            'tabbarColor' => '#666666',
            'tabbarSelectColor' => '#FF9800'
        ];
        
        #tabbar配置
        $data['tabbar'] = [
            'color' => '#666666',
            'selectedColor' => '#FF9800',
            'backgroundColor' => '#ffffff',
            'borderStyle' => 'white',
            'list' => [
                [
                    'pagePath' => 'pages/tabbar/home/index',
                    'text' => '首页',
                    'iconPath' => '/static/img/tabbar/home.png',
                    'selectedIconPath' => '/static/img/tabbar/home_on.png'
                ],
                [
                    'pagePath' => 'pages/tabbar/sort/index',
                    'text' => '分类',
                    'iconPath' => '/static/img/tabbar/sort.png',
                    'selectedIconPath' => '/static/img/tabbar/sort_on.png'
                ],
                [
                    'pagePath' => 'pages/tabbar/vip/index',
                    'text' => 'VIP',
                    'iconPath' => '/static/img/tabbar/vip.png',
                    'selectedIconPath' => '/static/img/tabbar/vip_on.png'
                ],
                [
                    'pagePath' => 'pages/tabbar/my/index',
                    'text' => '我的',
                    'iconPath' => '/static/img/tabbar/my.png',
                    'selectedIconPath' => '/static/img/tabbar/my_on.png'
                ]
            ]
        ];
        
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
        $data = [
            [
                'type' => 'search',
                'params' => [
                    'placeholder' => '搜索资源',
                    'style' => [
                        'backgroundColor' => '#f5f5f5',
                        'borderRadius' => 20
                    ]
                ]
            ],
            [
                'type' => 'banner',
                'params' => [
                    'list' => [
                        [
                            'url' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20241007/c514fd92769122adcbc96d94a919f00a.png',
                            'link' => ''
                        ]
                    ],
                    'autoplay' => true,
                    'interval' => 3000,
                    'indicatorDots' => true
                ]
            ],
            [
                'type' => 'nav',
                'params' => [
                    'list' => [
                        [
                            'name' => '首页',
                            'icon' => '/static/img/tabbar/home_on.png',
                            'link' => '/pages/tabbar/home/index'
                        ],
                        [
                            'name' => '分类',
                            'icon' => '/static/img/tabbar/sort_on.png',
                            'link' => '/pages/tabbar/sort/index'
                        ],
                        [
                            'name' => 'VIP',
                            'icon' => '/static/img/tabbar/vip_on.png',
                            'link' => '/pages/tabbar/vip/index'
                        ],
                        [
                            'name' => '我的',
                            'icon' => '/static/img/tabbar/my_on.png',
                            'link' => '/pages/tabbar/my/index'
                        ]
                    ],
                    'column' => 4
                ]
            ],
            [
                'type' => 'title',
                'params' => [
                    'title' => '推荐资源',
                    'moreText' => '更多',
                    'moreLink' => '/pages/tabbar/sort/index'
                ]
            ],
            [
                'type' => 'goods',
                'params' => [
                    'list' => [
                        [
                            'id' => 1,
                            'title' => '芝士盒知识付费系统',
                            'price' => 99,
                            'sales' => 100,
                            'thumb' => 'https://zpcms.oss-cn-beijing.aliyuncs.com/uploads/20241007/c514fd92769122adcbc96d94a919f00a.png',
                            'type' => 1
                        ]
                    ],
                    'column' => 2
                ]
            ]
        ];
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
        $type = $this->request->param('type/d', 0);
        $theme = [
            'themeColor' => '#FF9800',
            'themeColorLight' => '#FFE0B2',
            'tab_bg_color' => '#ffffff',
            'tab_text_color' => '#666666',
            'tab_text_color_on' => '#FF9800',
        ];
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