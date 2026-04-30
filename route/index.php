<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::rule('/login$', 'index/index/login'); // 登录
Route::rule('/register$', 'index/index/register'); // 注册
Route::rule('/findme', 'index/index/findme'); // 找回密码
Route::rule('/login/checkLogin', 'index/index/checkLogin'); // 检测登录
Route::rule('/login/loginOut', 'index/index/loginOut'); // 退出登录
Route::rule('/login/loginQrcode', 'index/index/loginQrcode'); // 扫码登录获取二维码
Route::rule('/login/scanQrcode', 'index/index/scanQrcode'); // 检查扫码状态
Route::rule('/course$', 'index/index/course'); // 课程
Route::get('/course/list<sid>', 'index/index/course')->pattern(['sid' => '\d+']); // 课程列表
Route::get('/course/show/<id>', 'index/index/show')->pattern(['id' => '\d+']); // 课程详情
Route::get('/course/video/<id>$', 'index/index/video')->pattern(['id' => '\d+']); // 视频播放
Route::get('/course/video/<id>/s<vid>', 'index/index/video')->pattern(['id' => '\d+', 'vid' => '\d+']); // 章节视频播放
Route::rule('/course/video/checkVideo', 'index/index/checkVideo'); //视频播放检测
Route::rule('/course/video/checkAudio', 'index/index/checkAudio'); //音频播放检测
Route::rule('/svip', 'index/index/svip'); //SVIP

Route::get('/article$', 'index/index/article'); //文章
Route::get('/article/list<sortid>', 'index/index/article')->pattern(['sortid' => '\d+']); // 课程列表
Route::get('/article/show/<id>', 'index/index/article_show')->pattern(['id' => '\d+']); // 文章详情

Route::get('/about$', 'index/index/about'); //关于我们
Route::get('/about/<id>', 'index/index/about'); //关于我们

Route::rule('/help$', 'index/index/help'); // 锟斤拷讯
Route::rule('/help/<id>', 'index/index/help_show')->pattern(['id' => '\d+']); // 帮助中心
Route::rule('/user$', 'index/index/user'); // 用户
Route::rule('/user/mycourse', 'index/index/mycourse'); // 我的课程
Route::rule('/user/mysvip', 'index/index/mysvip'); // 我的VIP
Route::rule('/user/buyvip', 'index/index/buyvip'); // 购买VIP
Route::rule('/user/myfav', 'index/index/myfav'); // 我的收藏
Route::rule('/user/info', 'index/index/myinfo'); //个人资料
Route::rule('/user/pwd', 'index/index/pwd'); //修改密码
Route::rule('/user/spread', 'index/index/spread'); //推广中心
Route::rule('/user/cash', 'index/index/cash'); //提现
Route::rule('/user/cashlist', 'index/index/cashlist'); //提现列表
Route::rule('/paycenter/order', 'index/index/order'); //下单中心
Route::rule('/paycenter/result', 'index/index/pay_result'); //支付结果页
Route::rule('/paycenter/wechat', 'index/index/wechat'); //微信扫码支付页
Route::rule('/paycenter/qrcode', 'index/index/qrcode'); //显示二维码
Route::rule('/paycenter/query', 'index/index/query_order'); //查询订单
Route::rule('/user/referrer', 'index/index/referrer'); //
Route::rule('/user/income', 'index/index/income'); //用户收益
Route::rule('/course/task', 'index/index/subtask'); //显示任务
//推广相关url
Route::get('/f_<from>$', 'index/index/index')->pattern(['from' => '\d+']); //分站推广链接
Route::get('/u_<invite_uid>$', 'index/index/index')->pattern(['invite_uid' => '\d+']); //用户推广链接
Route::get('/u_<invite_uid>_<rid>$', 'index/index/index')->pattern(['invite_uid' => '\d+', 'rid' => '\d+']); //资源推广链接