<?php

namespace app\api\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\AdminSite;
use app\common\model\Agent;
use app\common\model\Bill;
use app\common\model\Cash;
use app\common\model\PosterConfig;
use app\common\model\ResourceLevel;
use app\common\model\ResourceSort;
use app\common\model\Spread;
use app\common\model\{
    User as UserModel,
    UserColl,
    UserResource,
    Order,
    SvipTask,
    Svip,
    UserCash,
};
use app\api\validate\Check;
use app\common\model\UserSubscribe;
use Naixiaoxin\ThinkWechat\Facade;
use EasyWeChat\Kernel\Support\File;
use oss\Alioss;
use oss\Qcloud;
use PosterMaker\PosterMaker;
use think\Db;
use think\Exception;
use app\common\model\Qiniu;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use think\facade\Env;
use zp\Tree;

class User extends Common
{
    public function initialize()
    {
        parent::initialize();
        $this->middleware = [
            'cross',
            'Auth' => [
                'except' => [
                    'createPoster',
                ]
            ]
        ];
    }
    /**
     * 生成H5用户推广海报
     */
    public function spreadH5Poster()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.spreadPoster')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $admin_id = $this->request->param('from_id/d');
            $path = 'uploads/poster';
            $filename = 'spread_' . $uid . '_' . time() . '.png';
            $user = UserModel::where('id', $uid)->field('id,nickname,avatar')->find();
            list($res, $qrcode) = $this->createQrcode($data['links'], $uid);
            if (!$res) {
                $this->error($qrcode);
            }
            #查询站点名称
            $web_name= config('setting.web_name');
            $site_name = AdminSite::where('admin_id', $admin_id)->value('webname');
            if(!empty($site_name)){
                $web_name=$site_name;
            }
            #获取自定义海报数据
            $setting = [
                'main_text' => '邀您免费学习好课程',
                'main_text_color' => 'rgb(255,255,255)',
                'main_text_size' => '20',
                'sub_text' => '百套课程资源免费领取 分享赚佣金学习两不误',
                'url' => $this->request->domain() . '/static/common/images/poster_bg.jpg',
                'sub_text_color' => 'rgb(255,255,255)',
                'sub_text_size' => '22',
            ];
            $config = PosterConfig::where(['admin_id' => $admin_id, 'type' => 2])->find();
            if (!empty($config)) {
                $setting = json_decode($config->setting, true);
            }
            $setting['main_text_color'] = coverToRGB($setting['main_text_color']);
            $setting['sub_text_color'] = coverToRGB($setting['sub_text_color']);
            $width = 640;
            $height = 1138;
            $poster = new PosterMaker($width, $height, [255, 255, 255]);
            $poster->addImg($setting['url'], [0, 0], [640, 1138], 0);
            $poster->addImg($user->avatar, [256, 50], [128, 128], 64);
            $poster->addText($user->nickname, 24, [0, 220], [255, 255, 255]);

            $poster->addText($setting['main_text'], $setting['main_text_size'] * 1.5, [0, 300], $setting['main_text_color']);

            $sub_text = explode(' ', $setting['sub_text']);
            foreach ($sub_text as $k => $v) {
                $poster->addText($v, $setting['sub_text_size'] * 1.5, [0, 430 + ($k * ($setting['sub_text_size'] * 1.5) * 2.2)], $setting['sub_text_color']);
            }
            $poster->addImg($qrcode, [(($width / 2) - (320 / 2)), $height * 0.555], [320, 320]);
            $poster->addText($web_name, 24, [0, $height * 0.95], [255, 255, 255]);
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
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $path . '/' . $filename);
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
    protected function createQrcode($text, $uid)
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setWriterByName('png');
        $content = $qrCode->writeString();
        $path = 'uploads/h5/qrcode';
        $filename = 'qrcode_' . $uid  . '_' . time() . '.png';
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
            if ($result['status']!==200) {
                return [false, '生成失败'];
            }
            return [true, $result['data']['url']];
        }
    }

    /**
     * 生成课程推广海报
     */
    public function spreadPoster()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.spreadPoster')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $admin_id = $this->request->param('from_id/d');
            $path = 'uploads/poster';
            $filename = 'spread_' . $uid  . '_' . time() . '.png';
            $user = UserModel::where('id', $uid)->field('id,nickname,avatar')->find();
            if (is_douyin($this->plat_form)) {
                #获取小程序
                list($res, $qrcode) = $this->gettcode($data['scene'], $uid);
                if (!$res) {
                    $this->error($qrcode);
                }
            } else {
                list($res, $qrcode) = $this->wxappCode($data['scene'], $uid);
                if (!$res) {
                    $this->error($qrcode);
                }
            }
            #获取自定义海报数据
            $setting = [
                'main_text' => '邀您免费学习好课程',
                'main_text_color' => 'rgb(255,255,255)',
                'main_text_size' => '20',
                'sub_text' => '百套课程资源免费领取 分享赚佣金学习两不误',
                'url' => $this->request->domain() . '/static/common/images/poster_bg.jpg',
                'sub_text_color' => 'rgb(255,255,255)',
                'sub_text_size' => '22',
            ];
            $config = PosterConfig::where(['admin_id' => $admin_id, 'type' => 2])->find();
            if (!empty($config)) {
                $setting = json_decode($config->setting, true);
            }
            $setting['main_text_color'] = coverToRGB($setting['main_text_color']);
            $setting['sub_text_color'] = coverToRGB($setting['sub_text_color']);
            $width = 640;
            $height = 1138;
            $poster = new PosterMaker($width, $height, [255, 255, 255]);
            $poster->addImg($setting['url'], [0, 0], [640, 1138], 0);
            $poster->addImg($user->avatar, [256, 50], [128, 128], 64);
            $poster->addText($user->nickname, 24, [0, 220], [255, 255, 255]);
            $poster->addText($setting['main_text'], $setting['main_text_size'] * 1.5, [0, 300], $setting['main_text_color']);
            $sub_text = explode(' ', $setting['sub_text']);
            foreach ($sub_text as $k => $v) {
                $poster->addText($v, $setting['sub_text_size'] * 1.5, [0, 430 + ($k * ($setting['sub_text_size'] * 1.5) * 2.2)], $setting['sub_text_color']);
            }
            $poster->addImg($qrcode, [(($width / 2) - (320 / 2)), $height * 0.555], [320, 320]);
            $poster->addText(config('setting.web_name'), 24, [0, $height * 0.95], [255, 255, 255]);
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
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $path . '/' . $filename);
                if ($result['status']==200) {
                    $this->success('success', ['url' => $result['data']['url']]);
                } else {
                    $this->error('生成失败');
                }
            }
        }
    }

    /**
     * 获取推广小程序码
     */
    protected function wxappCode($scene, $uid)
    {
        try {
            $app = Facade::miniProgram();
            $optional['page'] = 'pages/share/jump';
            $response = $app->app_code->getUnlimit($scene, $optional);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $storage_type = config('setting.upload_storage');
                if ($storage_type == 'local') {
                    $filename = $response->saveAs('uploads/user/qrcode', $uid.'_'.time(), true);
                    if (!$filename) {
                        return [false, '二维码生成失败'];
                    }
                    $url = $this->request->domain() . '/uploads/user/qrcode/' . $filename;
                    return [true, $url];
                } elseif ($storage_type == 'aliyun') {
                    $contents = $response->saveContent('','');
                    $filename = $uid.'_'.time().File::getStreamExt($contents);
                    $oss = new Alioss();
                    $result = $oss->pudata($filename, $contents, 'uploads/user/qrcode');
                    if ($result['status'] == 200) {
                        $url = $result['data']['url'];
                        return [true, $url];
                    } else {
                        return [false, $result['msg']];
                    }
                } elseif ($storage_type == 'qcloud') {
                    $contents = $response->saveContent('','');
                    $filename = $uid.'_'.time().File::getStreamExt($contents);
                    $oss = new Qcloud();
                    $result = $oss->pudata($filename, $contents, 'uploads/user/qrcode');
                    if ($result['status'] == 200) {
                        $url = $result['data']['url'];
                        return [true, $url];
                    } else {
                        return [false, $result['msg']];
                    }
                } elseif ($storage_type == 'qiniu') {
                    $contents = $response->saveContent('','');
                    $filename = $uid.'_'.time().File::getStreamExt($contents);
                    $qiniu = new Qiniu;
                    $result = $qiniu->uploadData($contents, 'uploads/user/qrcode/' . $filename);
                    if ($result['status']==200) {
                        return [true, $result['data']['url']];
                    } else {
                        return [false, '生成失败'];
                    }
                }
            } else {
                return [false, '您的小程序还未发布'];
            }
        } catch (\EasyWeChat\Kernel\Exceptions\RuntimeException $e) {
            return [false, $e->getMessage()];
        } catch (\EasyWeChat\Kernel\Exceptions\InvalidArgumentException $e) {
            return [false, $e->getMessage()];
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 获取字节抖音小程序码
     * @return [type] [description]
     */
    protected function gettcode($scene, $uid)
    {
        try {
            $path = 'pages/share/jump?scene=' . $scene;
            $toutiao = new Toutiao();
            list($result, $content) = $toutiao->getQrcode(['path' => urlencode($path)]);
            if ($result) {
                $storage_type = config('setting.upload_storage');
                if ($storage_type == 'local') {
                    list($res, $filename) = $this->savePoster($content, 'uploads/qrcode', 't_' . $uid, true);
                    if (!$res) {
                        return [false, '二维码生成失败'];
                    }
                    return [true, $this->request->domain() . '/uploads/qrcode/' . $filename];
                } elseif ($storage_type == 'aliyun') {
                    $filename = 't_' . $uid . '.png';
                    $oss = new Alioss();
                    $result = $oss->pudata($filename, $content, 'uploads/qrcode');
                    if ($result['status'] !== 200) {
                        return [false, $result['msg']];
                    }
                    return [true, $result['data']['url']];
                } elseif ($storage_type == 'qcloud') {
                    $filename = 't_' . $uid . '.png';
                    $oss = new Qcloud();
                    $result = $oss->pudata($filename, $content, 'uploads/qrcode');
                    if ($result['status'] !== 200) {
                        return [false, $result['msg']];
                    }
                    return [true, $result['data']['url']];
                } elseif ($storage_type == 'qiniu') {
                    $filename = 't_' . $uid . '.png';
                    $qiniu = new Qiniu;
                    $result = $qiniu->uploadData($content, $filename);
                    if ($result['status']!==200) {
                        return [false, '生成失败'];
                    }
                    return [true, $result['data']['url']];
                }
            } else {
                return [false, '您的小程序还未发布'];
            }
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * 生成资源海报
     */
    public function createPoster()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.poster')->check($data)) {
                $this->error($validate->getError());
            }
            $token=$this->request->param('token/s');
            $nickname=config('setting.web_name');
            $avatar=config('setting.web_logo');
            if(!empty($token)){
                $user = UserModel::where(['token'=>$token,'status'=>1])->field('id,nickname,avatar')->find();
                if(!empty($user)){
                    $nickname=$user->nickname;
                    $avatar=$user->avatar;
                }
            }
            $id = $data['id'];
            $path = 'uploads/poster';
            $filename = time() . '_poster_' . $data['id'] . '.png';
            if (!empty($data['scene'])) {
                if (is_douyin($this->plat_form)) {
                    #获取小程序
                    list($res, $qrcode) = $this->gettcode($data['scene'], time());
                    if (!$res) {
                        $this->error($qrcode);
                    }
                } else {
                    #获取小程序
                    list($res, $qrcode) = $this->wxappCode($data['scene'], time());
                    if (!$res) {
                        $this->error($qrcode);
                    }
                }
            } else {
                $qrcode = $data['qrcode'];
            }
            $info = Spread::where('id', $id)->find();
            $price = '¥ ' . $info->price;
            $ios_type = config('setting.ios_close');
            $platform = getDeviceType();
            if ($ios_type == '1' && $platform == 'ios') {
                $price = $info->price . '积分';
            }
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
            $poster->addImg($avatar, [40, 40], [128, 128], 64);
            $poster->addText($nickname, 30, [220, 80], [255, 255, 255]);
            $poster->addText($setting['main_text'], 22, [220, 140], [255, 255, 255]);
            $poster->addBg(($width - 80), $height * 0.84, [40, $height * 0.13], $setting['bg_main_color'], 24);
            $poster->addImg($info->thumb, [80, ($height * 0.13) + 40], [$width - 160, $width - 240], 0);
            $poster->addText($price, 38, [80, $height * 0.73], [251, 55, 55]);
            $poster->addText($info->title, 34, [80, $height * 0.78], [10, 10, 10], '', 0, 600);
            $poster->addText($setting['sub_text'], 30, [80, $height * 0.91], [100, 100, 100], '', 0, 650);
            $poster->addText($info->sales . '人已获取', 22, [80, $height * 0.95], [254, 167, 0]);
            $poster->addImg($qrcode, [($width - 350), $height * 0.78], [256, 256]);
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
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($filename, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $filename);
                if ($result['status']==200) {
                    $this->success('success', ['url' => $result['data']['url']]);
                }else {
                    $this->error('生成失败');
                }
            }
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
     * 收藏/取消
     * @return [type] [description]
     */
    public function coll()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.coll')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $coll = 0;
            $info = UserColl::where(['rid' => $data['rid'], 'uid' => $uid])->find();
            if ($info) {
                $coll = 0;
                $info->delete();
            } else {
                $info = new UserColl;
                $info->rid = $data['rid'];
                $info->uid = $uid;
                $info->save();
                $coll = 1;
            }
            $this->success('success', ['coll' => $coll]);
        }
    }

    /**
     * 获取用户信息
     * @return [type] [description]
     */
    public function userinfo()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.userinfo')->check($data)) {
                $this->error($validate->getError());
            }
            $field = 'id,username,avatar,balance,mobile,nickname,token,exp_time,level,vid,realname,account,bankname,bankno,site_uid,login_time,ctime';
            $info = UserModel::field($field)->where('token', $data['token'])->where('status', 1)->find();
            if (!$info) {
                $this->error('获取信息失败');
            }
            #会员等级
            $exp_name = '长期有效';
            $vid = 0;
            if ($info->exp_time > time()) {
                $exp_name = date('Y-m-d', $info->exp_time);
                $vid = $info->vid;
            }
            $info->svip = ['id' => $vid, 'name' => Svip::getSvipName($info->vid, $info->exp_time), 'exp_name' => $exp_name];
            #已提现金额
            $info->cashed = UserCash::where(['uid' => $info->id, 'status' => 2])->sum('money') ?? 0;
            #是否已订阅
            $info->is_subscribe = 0;
            $userSub = UserSubscribe::where('uid', $info->id)->find();
            if ($userSub) {
                $info->is_subscribe = 1;
            }
            #站点信息
            $info->subsite = '';
            if (!empty($info->site_uid)) {
                $siteAgent = Agent::where(['id' => $info->site_uid, 'status' => 1])->where('exp_time', '>', time())->find();
                if ($siteAgent) {
                    $adminSite = AdminSite::where(['admin_id' => $info->site_uid])->find();
                    if ($adminSite) {
                        $adminSite->username = $siteAgent->username;
                        $adminSite->manage_url = $this->request->domain().'/partner';
                        $info->subsite = $adminSite;
                    }
                }
            }
            $this->success('success', $info);
        }
    }

    /**
     * 保存用户信息
     */
    public function saveInfo()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.saveinfo')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = UserModel::where(['id' => $uid, 'status' => 1])->find();
            if (!$info) {
                $this->error('获取信息失败');
            }
            if(!empty($data['password'])){
                $data['salt']=mt_rand(111111,999999);
                $data['pwd']=md5($data['password'].$data['salt']);
            }
            unset($data['password']);
            $data['utime'] = time();
            $result = $info->allowField(true)->save($data);
            if (!$result) {
                $this->error('保存失败');
            }
            $this->success('保存成功');
        }
    }

    /**
     * 我的收藏
     * @return [type] [description]
     */
    public function mycoll()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.mycoll')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $where[] = ['a.uid', '=', $uid];
            $list = UserColl::alias('a')->join('spread b', 'a.rid=b.id');
            if (!empty($data['sort_id'])) {
                $sortModel = new ResourceSort();
                $sort_arr = $sortModel->where('status', 1)->order('indexid asc')->select();
                if ($sort_arr) {
                    $sort_arr = $sort_arr->toArray();
                    $tree = new Tree();
                    $tree->init($sort_arr, 'pid');
                    $child_ids = $tree->getChildrenIds($data['sort_id'], true);
                    $list->join('spread_type c', 'a.rid=c.rid');
                    $where[] = ['c.sid', 'in', $child_ids];
                }
            }
            #关联VIP表
            $list = $list->leftJoin('svip s','s.id=b.is_vip');
            $list = $list->distinct(true)->field('b.id,b.title,b.thumb,b.type,b.price,b.sales,b.dis_price,b.desc,b.is_vip,b.level,a.ctime,s.name as svip_name')
                ->where('b.status', 1)
                ->where($where)
                ->limit($start, $data['limit'])
                ->order('a.ctime desc')
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['level_name'] = (new ResourceLevel())->getLevelName($v['level']);
                if($v['is_vip']==0){
                    $list[$k]['svip_name'] ='会员专享';
                }elseif($v['is_vip']==-1){
                    $list[$k]['svip_name'] ='';
                }
            }
            $this->success('success', ['list' => $list]);
        }
    }

    /**
     * 我购买的资源
     * @return [type] [description]
     */
    public function mybuyres()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.mybuyres')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $where[] = ['a.uid', '=', $uid];
            if (isset($data['type'])) {
                if ($data['type'] === 1) {//待支付
                    $where[] = ['a.status', '=', 0];
                } elseif ($data['type'] === 2) {//已支付
                    $where[] = ['a.status', '=', 1];
                }
            }
            $where[] = ['a.type', '=', 1];
            $field = 'a.id,a.ordno,a.status,a.rid,b.price,b.title,b.desc,b.sales,b.type,b.thumb,a.ctime';
            $list = Order::alias('a')->field($field)
                ->join('spread b', 'a.rid=b.id')->where($where)
                ->limit($start, $data['limit'])
                ->order('a.ctime desc')
                ->select();
            $this->success('success', ['list' => $list]);
        }
    }
    /**
     * 资源订单详情
     * @return [type] [description]
     */
    public function myOrderDetail()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.myOrderDetail')->check($data)) {
                $this->error($validate->getError());
            }
            if(empty($data['id']) && empty($data['ordno'])){
                $this->error('参数错误');
            }
            $uid = request()->uid;
            $field = 'a.id,a.ordno,a.status,a.ctime,a.ptime,a.money,a.pay_type,a.rid,b.price,b.title,b.type,b.thumb,b.level,b.sales';
            $detail = Order::alias('a')->field($field)
                ->join('spread b', 'a.rid=b.id')
                ->where('a.uid',$uid)
                ->where('a.id',$data['id'])
                ->whereOr('a.ordno',$data['ordno'])
                ->order('a.ctime desc')
                ->find();
            if($detail){
                $detail['level_name'] = (new ResourceLevel())->getLevelName($detail['level']);
                $detail['pay_status']=(new Order())->getPayStatus($detail['status']);
                $detail['pay_type']=(new Order())->getPayType($detail['pay_type']);
            }
            $this->success('success',$detail);
        }
    }
    /**
     * 我的任务资源
     * @return [type] [description]
     */
    public function mytaskres()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.mytaskres')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $where[] = ['a.uid', '=', $uid];
            $where[] = ['a.type', '=', 2];
            $field = 'a.id,a.rid,b.price,b.title,b.desc,b.sales,b.type,b.thumb';
            $list = UserResource::alias('a')->field($field)
                ->join('spread b', 'a.rid=b.id')
                ->where($where)
                ->limit($start, $data['limit'])
                ->order('a.ctime desc')
                ->select();
            $this->success('success', ['list' => $list]);
        }

    }

    /**
     * 取消订单
     * @return [type] [description]
     */
    public function qxorder()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.qxorder')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $i = Order::where('ordno', $data['ordno'])
                ->where('uid', $uid)
                ->update(['status' => 2]);
            if ($i) {
                $this->success('success');
            }
            $this->error('操作失败');
        }
    }

    /**
     * 继续支付订单（公众号和微信小程序）
     * @return [type] [description]
     */
    public function keeporder()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.keeporder')->check($data)) {
                $this->error($validate->getError());
            }
            $keep_type = $this->request->param('type/d', 0);
            $uid = request()->uid;
            $userinfo = UserModel::where('id', $uid)->find();
            $info = Order::where('ordno', $data['ordno'])
                ->where('uid', $uid)
                ->find();
            if (!$info) {
                $this->error('操作失败');
            }
            if ($info->status === 2) {
                $this->error('订单已取消');
            }
            if ($info->status === 1) {
                $this->error('订单已完成');
            }
            $ordno = date('YmdHis') . mt_rand(10000, 99999);
            $info->ordno = $ordno;
            $info->status = 0;
            $info->utime = time();
            if (!$info->save()) {
                $this->error('操作失败1');
            }
            if ($info->type === 1) {
                $order['body'] = '购买资源';
            } elseif ($info->type === 2) {
                $order['body'] = '购买会员';
            }
            switch ($keep_type) {
                case 1:#微信公众号
                    $order['out_trade_no'] = $ordno;
                    $order['total_fee'] = $info->money * 100;
                    $order['trade_type'] = 'JSAPI';
                    $order['openid'] = $userinfo->acc_openid;
                    $payment = Facade::payment('official_account'); // 微信支付
                    $result = $payment->order->unify($order);
                    if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                        $jssdk = $payment->jssdk;
                        $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                        $this->success('success', $config);
                    } else {
                        $this->error('支付下单失败');
                    }
                    break;
                default:#小程序
                    $order['out_trade_no'] = $ordno;
                    $order['total_fee'] = $info->money * 100;
                    $order['trade_type'] = 'JSAPI';
                    $order['openid'] = $userinfo->openid;
                    $payment = Facade::payment(); // 微信支付
                    $result = $payment->order->unify($order);
                    if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
                        $jssdk = $payment->jssdk;
                        $config = $jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                        $this->success('success', $config);
                    } else {
                        $this->error('支付下单失败');
                    }
                    break;
            }
        }
    }

    /**
     * 继续支付订单（H5）
     * @return [type] [description]
     */
    public function keepweborder()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.keeporder')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = Order::where('ordno', $data['ordno'])->where('uid', $uid)->find();
            if (!$info) {
                $this->error('操作失败');
            }
            if ($info->status === 2) {
                $this->error('订单已取消');
            }
            if ($info->status === 1) {
                $this->error('订单已完成');
            }
            $ordno = date('YmdHis') . mt_rand(10000, 99999);
            $info->ordno = $ordno;
            $info->status = 0;
            $info->utime = time();
            if (!$info->save()) {
                $this->error('操作失败1');
            }
            if ($info->type === 1) {
                $order['body'] = '购买资源';
            } elseif ($info->type === 2) {
                $order['body'] = '购买会员';
            }
            if ($data['pay_type'] == 1) {
                $order = [
                    'body' => $order['body'],
                    'out_trade_no' => $ordno,
                    'total_fee' => $info->money * 100,
                    'trade_type' => 'MWEB',
                    'scene_info' => json_encode(
                        [
                            "h5_info" => [
                                'type' => 'h5_info',
                                'wap_url' => config('setting.account_domain'),
                                'wap_name' => '购买会员'
                            ]
                        ]
                    )
                ];
                $payment = Facade::payment('official_account'); // 微信支付
                $result = $payment->order->unify($order);
                if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    Db::commit();
                    $this->success('success', ['pay_url' => $result['mweb_url'], 'ordno' => $ordno]);
                } else {
                    Db::rollback();
                    $this->error('下单失败');
                }
            } else {
                $aliPay = new \alipay\wap();
                $aliPay->setAppid(config('setting.alipay_appid'));
                $aliPay->setReturnUrl($data['return_url']);
                $aliPay->setNotifyUrl(config('setting.alipay_notify'));
                $aliPay->setRsaPrivateKey(config('setting.alipay_private_key'));
                $aliPay->setTotalFee($info->money);
                $aliPay->setOutTradeNo($ordno);
                $aliPay->setOrderName($order['body']);
                $result = $aliPay->doPay();
                $this->success('success', ['pay_url' => $result, 'ordno' => $ordno]);
            }
        }
    }

    /**
     * 继续支付订单（抖音小程序）
     * @return [type] [description]
     */
    public function keepttorder()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.keeporder')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $info = Order::where('ordno', $data['ordno'])->where('uid', $uid)->find();
            if (!$info) {
                $this->error('操作失败');
            }
            if ($info->status === 2) {
                $this->error('订单已取消');
            }
            if ($info->status === 1) {
                $this->error('订单已完成');
            }
            $ordno = date('YmdHis') . mt_rand(10000, 99999);
            $info->ordno = $ordno;
            $info->status = 0;
            $info->utime = time();
            if (!$info->save()) {
                $this->error('操作失败1');
            }
            if ($info->type === 1) {
                $body = '购买资源';
            } elseif ($info->type === 2) {
                $body = '购买会员';
            }
            $order = [
                'out_order_no' => $ordno,
                'subject' => $body,
                'body' => $body,
                'total_amount' => $info->money * 100
            ];
            $toutiao = new Toutiao(); // 微信支付
            $result = $toutiao->createOrder($order);
            if (isset($result['order_id'])) {
                $this->success('success', $result);
            } else {
                $this->error('下单失败');
            }
        }
    }

    /**
     * 获取会员任务信息
     * @return [type] [description]
     */
    public function sviptask()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.sviptask')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $admin_id = $this->request->param('from_id/d', 0);
            $default_admin_id = (new Admin())->getDefaultAdminId();
            $where = ['admin_id' => $admin_id];
            if ($admin_id != $default_admin_id) {
                $svip_num = Svip::where(['admin_id' => $admin_id, 'status' => 1,'type'=>0])->count();
                if ($svip_num == 0) {
                    $where = ['admin_id' => $default_admin_id];
                }
            }
            #查询会员当前等级
            $user = UserModel::where('id', $uid)->find();
            $is_vip = 0;
            if ($user->exp_time > time() && $user->vid > 0) {
                $is_vip = $user->vid;
            }
            $field = 'name,days,price,invite_num,level';
            #已完成任务数量
            $over_nums = UserModel::where('pid', $uid)->count();
            #已使用的任务数量
            $task_nums = SvipTask::where('uid', $uid)->sum('nums');
            if (empty($task_nums)) {
                $task_nums = 0;
            }
            $nums = $over_nums - $task_nums;
            #当前是否有会员等级
            if ($is_vip > 0) {
                #不满足条件
                $not_with = Svip::where('id', '>', $is_vip)->where('invite_num','>',0)->where('invite_num', '>', $nums)->where(['status' => 1, 'type' => 0])->where($where)->field($field)->find();
                #满足条件
                $with = Svip::where('id', '>', $is_vip)->where('invite_num','>',0)->where('invite_num', '<=', $nums)->where(['status' => 1, 'type' => 0])->where($where)->field($field)->find();
            } else {
                #不满足条件
                $not_with = Svip::where('invite_num', '>', $nums)->where('invite_num','>',0)->where(['status' => 1, 'type' => 0])->where($where)->field($field)->find();
                #满足条件
                $with = Svip::where('invite_num', '<=', $nums)->where('invite_num','>',0)->where($where)->where(['status' => 1, 'type' => 0])->field($field)->find();
            }
            if ($not_with) {
                $not_with->differ = ($not_with->invite_num - $nums);
                $not_with->perent = round(($nums / $not_with->invite_num) * 100);
            } else {
                $not_with = '';
            }
            if(empty($with) && empty($not_with)){
                $taskInfo=null;
            }else{
                $taskInfo=[
                    'with'=>$with,//满足任务条件
                    'notwith'=>$not_with,//不满足任务条件
                ];
            }
            $this->success('success', $taskInfo);
        }
    }

    /**
     * 用户订阅操作
     */
    public function subscribe()
    {
        try {
            if (request()->isPost()) {
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('User.subscribe')->check($data)) {
                    $this->error($validate->getError());
                }
                $rid = empty($data['rid']) ? 0 : $data['rid'];
                $uid = request()->uid;
                switch ($data['type']) {
                    case 1:#新资源上线
                        $temp_id = config('setting.subscribe_new_id');
                        $name = '新资源上线模板';
                        break;
                    case 2:#新任务接收
                        $temp_id = config('setting.subscribe_task_id');
                        $name = '新任务接受模板';
                        break;
                    case 3:#提现到账通知
                        $temp_id = config('setting.subscribe_cashed_id');
                        $name = '提现通知模板';
                        break;
                }
                $userSubscribe = new UserSubscribe();
                $subscribe = $userSubscribe->where(['uid' => $uid, 'temp_id' => $temp_id, 'rid' => $rid])->find();
                if ($subscribe) {
                    $subscribe->result = $data[$temp_id];
                    $subscribe->ctime = time();
                    $result = $subscribe->save();
                    if (!$result) {
                        $this->error('订阅操作失败');
                    }
                } else {
                    $from_id = $this->request->param('from_id') ?? 1;
                    $sub_list = [
                        'admin_id' => $from_id,
                        'rid' => $rid,
                        'temp_id' => $temp_id,
                        'name' => $name,
                        'result' => $data[$temp_id],
                        'ctime' => time(),
                        'uid' => $uid
                    ];
                    $result = $userSubscribe->create($sub_list, true);
                    if (!$result) {
                        $this->error('订阅操作失败');
                    }
                }
                $this->success('订阅操作成功');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取任务会员
     * @return [type] [description]
     */
    public function obsviptask()
    {
        try {
            if (request()->isPost()) {
                Db::startTrans();
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('User.obsviptask')->check($data)) {
                    $this->error($validate->getError());
                }
                $uid = request()->uid;
                $admin_id = $this->request->param('from_id/d', 0);
                $default_admin_id = (new Admin())->getDefaultAdminId();
                $where = ['admin_id' => $admin_id];
                if ($admin_id != $default_admin_id) {
                    $svip_num = Svip::where(['admin_id' => $admin_id, 'status' => 1])->count();
                    if ($svip_num == 0) {
                        $where = ['admin_id' => $default_admin_id];
                    }
                }
                $over_nums = UserModel::where('pid', $uid)->count();
                #已使用的任务数量
                $task_nums = SvipTask::where('uid', $uid)->sum('nums');
                if (empty($task_nums)) {
                    $task_nums = 0;
                }
                #去重已完成任务数量
                $nums = $over_nums - $task_nums;
                if ($nums <= 0) {
                    $this->error('升级失败，升级任务条件未完成');
                }
                $field = 'id,name,days,price,invite_num,level';
                $svip = Svip::where('invite_num', '<=', $nums)->where('invite_num','>',0)->where($where)->field($field)->order('level desc')->find();
                if (empty($svip)) {
                    $this->error('升级失败，当前无更高会员等级');
                }
                $task = [
                    'uid' => $uid,
                    'nums' => $svip->invite_num,
                    'vid' => $svip->id,
                    'admin_id' => $admin_id,
                    'ctime' => time()
                ];
                $svipTask = SvipTask::create($task, true);
                if (!$svipTask) {
                    $this->error('升级失败，请稍后再试');
                }
                $arr['vid'] = $svip->id;
                $arr['exp_time'] = time() + ($svip->days * 86400);
                $res = UserModel::where('id', $uid)->update($arr);
                if (!$res) {
                    Db::rollback();
                    $this->error('升级失败，请稍后再试');
                }
                Db::commit();
                $this->success('升级成功');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 提现
     * @return [type] [description]
     */
    public function cash()
    {
        try {
            if (request()->isPost()) {
                Db::startTrans();
                $data = input('post.');
                $validate = new Check;
                if (!$validate->scene('User.cash')->check($data)) {
                    $this->error($validate->getError());
                }
                $uid = request()->uid;
                $userinfo = UserModel::where(['id'=>$uid,'status'=>1])->find();
                if(empty($userinfo)){
                    $this->error('用户账号不存在或被禁用');
                }
                switch ($data['type']) {
                    case 1:#支付宝
                        if(intval(config('setting.cash_isalipay')) == 2) {
                            if (empty($data['realname'])) {
                                $this->error('请正确输入支付宝实名姓名');
                            }
                            if (empty($data['account'])) {
                                $this->error('请正确输入支付宝账号');
                            }
                        }else{
                            if (empty($data['realname'])) {
                                $this->error('请正确输入支付宝实名姓名');
                            }
                            if (empty($data['image'])) {
                                $this->error('请上传支付宝收款码');
                            }
                        }
                        break;
                    case 2:#微信
                        if(intval(config('setting.cash_iswechat')) == 2){
                            if (empty($data['realname'])) {
                                $this->error('请正确输入微信实名姓名');
                            }
                        }else{
                            if (empty($data['realname'])) {
                                $this->error('请正确输入微信实名姓名');
                            }
                            if (empty($data['image'])) {
                                $this->error('请上传微信收款码');
                            }
                        }
                        break;
                    case 3:#银行卡
                        if (empty($data['realname'])) {
                            $this->error('请正确输入银行开户名');
                        }
                        if (empty($data['bankname'])) {
                            $this->error('请正确输入开户行银行名称');
                        }
                        if (empty($data['bankno'])) {
                            $this->error('请正确输入银行卡卡号');
                        }
                        $userinfo->bankname = $data['bankname'];
                        $userinfo->bankno = $data['bankno'];
                        break;
                }
                $userinfo->realname = $data['realname'];
                $userinfo->save();
                $arr1 = explode('-', config('setting.cash_time'));
                if (date('H') < $arr1[0] || date('H') > $arr1[1]) {
                    $this->error('当前不在提现时间内');
                }
                $cash_num = UserCash::where('uid', $uid)
                    ->where('ctime', 'BETWEEN', [strtotime(date('Y-m-d')), strtotime(date('Y-m-d') . ' 23:59:59')])
                    ->count();
                if ($cash_num >= config('setting.cash_num')) {
                    $this->error('超过每日提现次数');
                }
                if ($data['money'] > $userinfo->balance) {
                    $this->error('可提现余额不足');
                }
                if ($data['money'] < config('setting.cash_min')) {
                    $this->error('低于最低提现金额');
                }
                $ordno = 'C' . date('YmdHis') . mt_rand(10000, 99999);
                $arr['ordno'] = $ordno;
                $arr['realname'] = $data['realname'];
                $arr['account'] = $data['account'];
                $arr['bankname'] = $data['bankname'];
                $arr['bankno'] = $data['bankno'];
                $arr['money'] = $data['money'];
                $arr['fee'] = bcmul($data['money'], config('setting.cash_fee') / 100, 2);
                $arr['amount'] = $arr['money'] - $arr['fee'];
                $arr['image'] = $data['image'];
                $arr['uid'] = $uid;
                $arr['type'] = $data['type'];
                $arr['ctime'] = time();
                if ($data['money'] < config('setting.cash_passed')) {
                    $arr['status'] = 1;
                } else {
                    $arr['status'] = 0;
                }
                $cashed = UserCash::create($arr, true);
                if (!$cashed) {
                    $this->error('操作异常，提交失败');
                }
                $remark = '提现' . $arr['money'] . '元，手续费' . $arr['fee'] . '元，实际到账' . $arr['amount'] . '元。';
                list($res, $info) = Bill::money(2, 3, $arr['money'], $uid, $remark, $cashed->id);
                if (!$res) {
                    Db::rollback();
                    $this->error($info);
                }
                Db::commit();
                $this->success('success');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 提现列表
     * @return [type] [description]
     */
    public function cashlist()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.cashlist')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $list = UserCash::where('uid', $uid)->limit($start, $data['limit'])->order('ctime desc')->select();
            foreach ($list as $k => $v){
                if (empty($v['utime'])) {
                    $list[$k]['utime'] = ' - ';
                } else {
                    $list[$k]['utime'] = date('Y-m-d H:i', $v['utime']);
                }
                $list[$k]['mch_id'] = config('setting.mch_id');
                $list[$k]['appid'] = config('setting.app_id');
                if(empty($v['package'])){
                    $list[$k]['package']='';
                }
            }
            $this->success('success', ['list' => $list]);
        }
    }
    /**
     * 更新确认收款状态
     * @return void
     */
    public function updateConfirm()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.updateConfirm')->check($data)) {
                $this->error($validate->getError());
            }
            $userCash=UserCash::where(['id'=>$data['id'],'status'=>2,'type'=>2])->find();
            if(!$userCash){
                $this->error('操作异常，更新失败');
            }
            $userCash->confirm=$data['confirm'];
            $userCash->utime=time();
            $result=$userCash->save();
            if (!$result) {
                $this->error('操作异常，更新失败');
            }
            $this->success('success');
        }
    }
    /**
     * 我的团队列表
     * @return [type] [description]
     */
    public function teamlist()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $list = \app\common\model\User::field('id,nickname,avatar,ctime')
                ->withJoin(['svip' => ['name']])
                ->where('user.pid', $uid)
                ->limit($start, $data['limit'])
                ->order('user.ctime desc')
                ->select();
            $total = \app\common\model\User::where(['pid' => $uid])->count('id');
            foreach ($list as $k => $v) {
                $list[$k]['level_name'] = '普通用户';
                if (!empty($v['svip'])) {
                    $list[$k]['level_name'] = $v['svip']['name'];
                }
            }
            $this->success('success', ['list' => $list, 'total' => $total]);
        }
    }

    /**
     * 收益明细
     * @return [type] [description]
     */
    public function incomelist()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $validate = new Check;
            if (!$validate->scene('User.incomelist')->check($data)) {
                $this->error($validate->getError());
            }
            $uid = request()->uid;
            $start = ($data['page'] - 1) * $data['limit'];
            $billModel = new Bill();
            $list = $billModel->alias('a')
            ->join('order b','a.order_id=b.id')
            ->join('user c','b.uid=c.id')
            ->join('spread d','b.rid=d.id','left')
            ->join('svip e','b.vid=e.id','left')
            ->where('a.uid', $uid)
            ->where('a.type', 'in',[1,2])
            ->field('a.id,a.mode,a.type,a.money,a.ctime,b.type as o_type,b.vid,b.rid,c.nickname,c.avatar,d.title,d.type as spread_type,e.name')
            ->limit($start, $data['limit'])
            ->order('a.ctime desc')
            ->select();
            foreach ($list as $k => $v) {
                if($v['o_type'] == 1){
                    if(!empty($v['title'])){
                        $list[$k]['order_name'] = $v['title'];
                        $list[$k]['order_type'] = $v['spread_type'];
                    }else{
                        $list[$k]['order_name'] = '未知';
                        $list[$k]['order_type'] = 0;
                    }
                }elseif($v['o_type'] == 2){
                    $list[$k]['order_name'] = $v['name'];
                    $list[$k]['order_type'] = 0;
                }
                $list[$k]['type_name'] = $billModel->getTypeName($v['type']);
                $list[$k]['utime'] = date('Y-m-d H:i', strtotime($v['ctime']));
            }
            $this->success('success', ['list' => $list]);
        }
    }

    /**
     * 没有的收益统计
     * @return [type] [description]
     */
    public function incomeStat()
    {
        if (request()->isPost()) {
            $uid = request()->uid;
            #余额
            $balance = \app\common\model\User::where('id', $uid)->value('balance');
            #今日收入
            $today = Bill::where(['uid' => $uid, 'mode' => 1])->whereIn('type', '1,2')->whereTime('ctime', 'today')->sum('money');
            #昨日收入
            $yesday = Bill::where(['uid' => $uid, 'mode' => 1])->whereIn('type', '1,2')->whereTime('ctime', 'yesterday')->sum('money');
            #本月收入
            $monthday = Bill::where(['uid' => $uid, 'mode' => 1])->whereIn('type', '1,2')->whereTime('ctime', 'month')->sum('money');
            #累计收入
            $total = Bill::where(['uid' => $uid, 'mode' => 1])->whereIn('type', '1,2')->sum('money');

            $this->success('success', [
                'balance' => $balance,
                'today' => $today ?? 0.00,
                'yesday' => $yesday ?? 0.00,
                'month' => $monthday ?? 0.00,
                'total' => $total ?? 0.00
            ]);
        }
    }

    /**
     * 修改头像
     */
    public function upload()
    {
        if ($this->request->isPost()) {
            $uid = request()->uid;
            $file = request()->file('file');
            $ext = '.' . getFileExt($file->getInfo('name'));
            $content = file_get_contents($file->getInfo('tmp_name'));
            $file_name = 'img_' .$uid.'_'.time() . $ext;
            $path = 'uploads/front';
            $storage_type = config('setting.upload_storage');
            if ($storage_type == 'local') {
                #移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
                if ($info) {
                    $path = str_replace('\\', '/', $info->getSaveName());
                    $this->success('success', ['url' => $this->request->domain() . '/uploads/' . $path]);
                } else {
                    $this->error('图片上传失败');
                }
            } elseif ($storage_type == 'aliyun') {
                $oss = new Alioss();
                $result = $oss->pudata($file_name, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qcloud') {
                $oss = new Qcloud();
                $result = $oss->pudata($file_name, $content, $path);
                $this->success('success', ['url' => $result['data']['url']]);
            } elseif ($storage_type == 'qiniu') {
                $qiniu = new Qiniu;
                $result = $qiniu->uploadData($content, $file_name);
                if ($result['status']==200) {
                    $this->success('success', ['url' => $result['data']['url']]);
                } else {
                    $this->error('上传失败');
                }
            }
        }
    }
    /**
     * 获取微信绑定手机号码
     */
    public function getMobile()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $miniProgram = Facade::miniProgram();
            $access_token = $miniProgram->access_token->getToken();
            $api_url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . $access_token['access_token'];
            $params = [
                'code' => $data['code']
            ];
            $result = httpRequest($api_url, 'POST', $params, [], false, true);
            $result = json_decode($result, true);
            if ($result['errcode'] !== 0) {
                $this->error($result['errmsg']);
            }
            $this->success('success', $result['phone_info']);
        }
    }
}