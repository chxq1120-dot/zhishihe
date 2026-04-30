<?php
namespace app\api\validate;

use think\Validate;

class Check extends Validate
{
    protected $rule = [
        'sort_id' => 'require|number',
        'id'=>'require|number',
        'page'=>'require|number',
        'limit'=>'require|number',
        'type'=>'require|number',
        'code'=>'require',
        'nickname'=>'require',
        'avatar'=>'require',
        'tt_openid'=>'require|length:18,36|alphaDash',
        'openid'=>'require|length:18,48|alphaDash',
        'unionid'=>'require|length:18,48|alphaDash',
        'rid'=>'require|number',
        'token'=>'require',
        'ordno'=>'require',
        'money'=>'require',
        'fid'=>'require',
        'path'=>'require',
        'data'=>'require',
        'cid'=>'require',
        'mobile'=>'require|mobile',
        'pwd'=>'require|length:6,25',
        'rpwd'=>'require|length:6,25',
        'username'=>'require|length:6,11',
        'pay_type'=>'require|number',
        'platform'=>'require|alphaDash',
        'links'=>'require',
        'pointJson'=>'require',
        'skeyCode'=>'require'
    ];
    protected $scene = [
        'Index.article'  =>  ['sort_id'],//资讯公告
        'Index.articledetail'=>['id'],//资讯详情
        'Index.getxcode'=>['token','id','type','path'],//获取小程序码
        'Index.sendsms'=>['mobile','skeyCode'],//发送短信验证码
        'Resource.resource'=>['page','limit'],//资讯列表
        'Resource.resdetail'=>['id'],//资讯详情
        'Resource.subrestask'=>['id'],//资源任务完成回调
        'Resource.subtaskvideo'=>['token','rid'],//资源激励视频任务完成回调
        'Resource.receivetask'=>['token','id'],//领取资源任务
        'Resource.download'=>['token','id'],//文档下载
        'Login.subcode'=>['code'],//提交登录jscode
        'Login.login'=>['nickname','avatar','openid'],//用户登录
        'Login.toutiaologin'=>['nickname','avatar','openid'],//字节用户登录
        'Login.register'=>['mobile','code','pwd','rpwd'], //注册
        'Login.oauth'=>['code'], //授权登录
        'Login.weblogin'=>['username','pwd'], //web页登录
        'Login.resetpwd'=>['mobile','code','pwd','rpwd'], //重置密码
        'User.coll'=>['rid','token'],//收藏/取消
        'User.userinfo'=>['token'],//获取用户信息
        'User.mybuyres'=>['token','limit','page'],//我的购买资源
        'User.mytaskres'=>['token','limit','page'],//我的任务资源
        'User.myOrderDetail'=>['token'],//资源订单详情
        'User.mycoll'=>['token','limit','page'],//我的收藏
        'User.qxorder'=>['token','ordno'],//取消订单
        'User.keeporder'=>['token','ordno'],//继续支付订单
        'User.sviptask'=>['token'],//获取会员任务信息
        'User.obsviptask'=>['token'],//获取任务会员
        'User.cash'=>['token','money','type'],//提现
        'User.cashlist'=>['token','limit','page'],//提现列表
        'User.updateConfirm'=>['token','id','confirm'],//更新确认收款状态
        'User.saveinfo'=>['token'],//保存用户信息
        'User.subscribe'=>['token'],//保存用户订阅
        'User.poster'=>['id'],//生成资源海报
        'User.posterh5'=>['token','id','links'],//生成H5资源海报
        'User.spreadPoster'=>['token','scene'],//生成推广海报
        'Order.buyres'=>['token','id'],//购买资源
        'Order.buyvip'=>['token','id'],//购买VIP
        'Order.accbuyres'=>['token','id'],//公众号购买资源
        'Order.accbuyvip'=>['token','id'],//公众号购买VIP
        'Order.webbuyres'=>['token','id','pay_type'],//H5购买资源
        'Order.webbuyvip'=>['token','id','pay_type'],//H5购买VIP
        'Order.queryOrder'=>['token','ordno'],//H5查询订单是否支付
        'Svip.svipinfo'=>['token','id'],//获取会员权限信息
        'Market.shnotify'=>['token','id'], //审核回调
        'Market.handlebuy'=>['token','data'], //购买回调
        'Payorder.buy'=>['token','id','type'],//购买
        'Resource.viewLog'=>['token','id','cid'],//记录播放记录
        'Resource.kammiTask'=>['token','id'],//卡密记录
        'Resource.buykmList'=>['token','id'],//卡密列表
        'User.spreadH5Poster'=>['token','links'],//生成用户H5推广海报
        'User.incomelist'=>['token'],//收益明细
        'Login.smslogin'=>['mobile','code'],//短信登录
        'Order.paymentApp'=>['id','type'],//APP微信支付
        'Order.createOrder'=>['id','type','pay_type','platform'],//统一下单支付接口
        'Order.handleAlipay'=>['ordno'],//微信内支付宝支付
        'Resource.handSpread'=>['token','id'],//助力资源
        'Resource.getHandList'=>['id','page','limit'],//助力列表
        'Sms.captcha'=>['token','pointJson'],//验证码验证
    ];
}