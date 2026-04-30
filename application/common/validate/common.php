<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/10/25
 * Time: 11:54
 */

namespace app\common\validate;
use think\Validate;

class common extends Validate
{
    protected $rule = [
        'username' => 'require|length:4,20',
        'password' => 'require|length:6,20',
        'pwd' => 'require|length:6,20',
        'realname' => 'require|length:2,10|chsAlpha',
        'mobile' => 'require|mobile',
        'company' => 'require|length:2,30|chsAlpha',
        'province_id' => 'require',
        'city_id' => 'require',
        'county_id' => 'require',
        'name' => 'require|length:2,30',
        'device_sn' => 'require|length:2,30',
        'indexid' => 'require|number',
        'code' => 'require',
        'money'=> 'require|float',
        'amount'=> 'require|float',
        'title' => 'require|length:2,50',
        'content' => 'require|min:2',
        'url' => 'require',
        'link' => 'require',
        'thumb' => 'require',
        'label' => 'require|length:2,30',
        'desc' => 'require|length:2,100',
        'app_id' => 'require',
        'app_key' => 'require',
        'pay_channel' => 'require',
        'pay_url' => 'require',
        'min_take' => 'require|float',
        'cash_fee' => 'require|float',
        'take_num' => 'require|float',
        'avatar' => 'require',
    ];
    protected $message = [
        'username.require' => '请输入用户名',
        'username.length' => '用户名长度6~20位',
        'username.alphaDash' => '用户名由字母数字组成',
        'password.require' => '请输入登录密码',
        'password.length' => '登录密码长度6~20位',
        'pwd.require' => '请输入登录密码',
        'pwd.length' => '登录密码长度6~20位',
        'realname.require' => '请输入联系人姓名',
        'realname.length' => '联系人姓名长度2~10位',
        'realname.chsAlpha' => '联系人姓名由中文或字母组成',
        'mobile.require' => '请输入联系人手机',
        'mobile.mobile' => '联系人手机输入错误',
        'company.require' => '请输入公司名称',
        'company.length' => '公司名称长度2~30位',
        'company.chsAlpha' => '公司名称由中文或字母组成',
        'province_id.require' => '请选择所属省份',
        'city_id.require' => '请选择所属城市',
        'county_id.require' => '请选择所属区县',
        'name.require' => '请输入名称',
        'name.length' => '名称长度2~30位',
        'indexid.require' => '请输入排序',
        'indexid.number' => '排序只能是数字',
        'code.require' => '请输入编码',
        'code.number' => '编码只能是数字',
        'money.require' => '请输入金额',
        'money.float' => '金额只能是数字',
        'amount.require' => '请输入金额',
        'amount.float' => '金额只能是数字',
        'title.require' => '请输入标题',
        'title.length' => '标题长度2~50个字符',
        'content.require' => '请输入内容介绍',
        'content.min' => '内容介绍长度最少2个字符',
        'url.require' => '请上传图片',
        'link.require' => '请输入链接地址',
        'thumb.require' => '请上传封面图片',
        'label.require' => '请输入标识',
        'label.length' => '标识长度2~30个字符',
        'desc.require' => '请输入描述',
        'desc.length' => '描述长度2~100个字符',
        'app_id.require' => '请输入通道app_id',
        'app_key.require' => '请输入通道app_key',
        'min_take.require' => '请输入代理抽成',
        'min_take.float' => '代理抽成只能是数字',
        'cash_fee.require' => '请输入提现手续费比例',
        'cash_fee.float' => '提现手续费比例只能是数字',
        'take_num.require' => '请输入扣量数字',
        'take_num.float' => '扣量数字只能是数字',
        'avatar.require' => '请上传头像',
    ];
    protected $scene = [
        'addagent'  =>  ['username','password','min_take'],
        'editagent'  =>  ['avatar'],
        'addtags'  =>  ['name','indexid'],
        'edittags'  =>  ['name','indexid'],
        'addcity'  =>  ['name','code'],
        'editcity'  =>  ['name','code'],
        'addswiper'  =>  ['title','url','link'],
        'editswiper'  =>  ['title','url','link'],
        'addword'  =>  ['name','indexid'],
        'editword'  =>  ['name','indexid'],
        #提现
        'addcash'=>['money','amount'],
        'editcash'=>['money','amount'],
        #目录
        'addsort'=>['name'],
        'editsort'=>['name'],
        #文章
        'addarticle'=>['title','content'],
        'editarticle'=>['title','content'],
        #文章类目
        'addarticlesort'=>['name'],
        'editarticlesort'=>['name'],
        #添加布局
        'addTheme'=>['name','desc'],
        'editTheme'=>['name','desc'],
        #等级
        'addlevel'=>['name','indexid'],
        'editlevel'=>['name','indexid'],
        #友链
        'addlinks'=>['name','indexid'],
        'editlinks'=>['name','indexid'],
        #行情
        'addFuture'=>['name','code'],
        'editFuture'=>['name','code'],
    ];
}