<?php
// +----------------------------------------------------------------------
// | ZHIPALLWCCE [ Wisdom Create Cloud Common ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2021 http://www.zhipall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: workrd <304609001@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\common\model\UserQrcode;
use app\common\model\UserThird;
use app\common\model\WechatKey;
use app\common\model\WechatRece;
use app\common\model\WechatSub;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use Naixiaoxin\ThinkWechat\Facade;
use think\Db;

class Wechat extends Common
{
    public function initialize()
    {
        parent::initialize();
        $this->middleware = [
            'cross',
            'Auth' => [
                'except' => [
                    'index',
                ]
            ]
        ];
    }

    /**
     * 处理微信公众号发送的消息
     */
    public function index()
    {
        $app = Facade::officialAccount();
        $app->server->push(function ($message) use ($app) {
            switch ($message['MsgType']) {
                case 'event':
                    switch (strtoupper($message['Event'])) {
                        case 'SCAN':#扫码
                            $openId = $message['FromUserName'];
                            $key = str_replace('qrscene_', '', $message['EventKey']);
                            $ticket = $message['Ticket'];
                            //处理扫码登录事件
                            $this->handleScan($key, $openId, $ticket, $app);
                            break;
                        case 'CLICK':#点击
                            if(!empty($message['EventKey'])){
                                $keywords = WechatKey::where('name', 'like', '%' . $message['EventKey'] . '%')->where('status',1)->find();
                                if(!$keywords){
                                    return $this->handleReply(2);
                                }
                                switch ($keywords->type) {
                                    case 'text':#文本
                                        return new Text($keywords->content);
                                        break;
                                    case 'image':#图片
                                        return new Image($keywords->content);
                                        break;
                                    case 'voice':#音频
                                        return new Voice($keywords->content);
                                        break;
                                    case 'video':#视频
                                        return new Video($keywords->content);
                                        break;
                                    case 'news':#图文
                                        break;
                                }
                                break;
                            }
                        case 'SUBSCRIBE':#关注
                            if(!empty($message['EventKey'])){
                                $openId = $message['FromUserName'];
                                $key = str_replace('qrscene_', '', $message['EventKey']);
                                $ticket = $message['Ticket'];
                                //处理扫码登录事件
                                $this->handleScan($key, $openId, $ticket, $app);
                            }else{
                                return $this->handleReply(1);
                            }
                            break;
                        case 'UNSUBSCRIBE':#取消关注
                            break;
                    }
                    break;
                case 'text':#获取关键字回复
                    $keywords = WechatKey::where('name', 'like', '%' . $message['Content'] . '%')->where('status',1)->find();
                    if(!$keywords){
                        return $this->handleReply(2);
                    }
                    switch ($keywords->type) {
                        case 'text':#文本
                            return new Text($keywords->content);
                            break;
                        case 'image':#图片
                            return new Image($keywords->content);
                            break;
                        case 'voice':#音频
                            return new Voice($keywords->content);
                            break;
                        case 'video':#视频
                            return new Video($keywords->content);
                            break;
                        case 'news':#图文
                            break;
                    }
                    break;
                default:
                    return $this->handleReply(2);
                    break;
            }
        });
        $response = $app->server->serve();
        $response->send();
        exit;
    }

    /*
     * 处理消息回复
     */
    protected function handleReply($type)
    {
        switch ($type) {
            case 1:#关注回复
                $subscribe = WechatSub::where('status', 1)->find();
                if (!$subscribe) {
                    return '感谢您的关注';
                }
                switch ($subscribe->type) {
                    case 'text':#文本
                        return new Text($subscribe->content);
                        break;
                    case 'image':#图片
                        return new Image($subscribe->content);
                        break;
                    case 'voice':#音频
                        return new Voice($subscribe->content);
                        break;
                    case 'video':#视频
                        return new Video($subscribe->content);
                        break;
                    case 'news':#图文
                        break;
                }
                break;
            case 2:#收到消息回复
                $receive = WechatRece::where('status', 1)->find();
                if ($receive) {
                    switch ($receive->type) {
                        case 'text':#文本
                            return new Text($receive->content);
                            break;
                        case 'image':#图片
                            return new Image($receive->content);
                            break;
                        case 'voice':#音频
                            return new Voice($receive->content);
                            break;
                        case 'video':#视频
                            return new Video($receive->content);
                            break;
                        case 'news':#图文
                            break;
                    }
                }
                break;
        }
    }

    /**
     * 处理微信扫码登录
     */
    protected function handleScan($key, $openId, $ticket, $app)
    {
        if (strlen($key) == 32 && !empty($openId)) {
            $userQrcode = new UserQrcode();
            $uQrcode = $userQrcode->where(['key' => $key, 'ticket' => $ticket])->find();
            if (!$uQrcode) {
                return [false, '二维码已经失效'];
            }
            #执行登录操作，如果没有
            $user = $app->user->get($openId);
            $user['nickname'] = empty($user['nickname']) ? '用户' . rand_string(6, 0) : $user['nickname'];
            $user['avatar'] = empty($user['headimgurl']) ? config('setting.web_logo') : $user['headimgurl'];
            $user['sex'] = empty($user['sex']) ? '0' : $user['sex'];
            $userThird = new UserThird();
            #获取第三方保存的登录ID
            $user_third_id = $userThird->saveThird($user, 2);
            $info = $userThird->where(['id' => $user_third_id])->find();
            if (!$info) {
                return [false, '登录失败'];
            }
            if ($info->uid == 0) {
                $user['rid'] = $uQrcode->rid;
                $user['pid'] = $uQrcode->invite_uid;
                $user['admin_id'] = $uQrcode->from_id;
                list($res, $newUser) = (new \app\common\model\User())->createUser($user);
                if (!$res) {
                    return [false, $newUser];
                }
                $info->uid = $newUser->id;
                $info->utime = time();
                $info->save();
            }
            $uQrcode->uid = $info->uid;
            $uQrcode->utime = time();
            $uQrcode->save();
            return [true, '登录成功'];
        }
    }
}