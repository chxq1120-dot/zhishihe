<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\common\model;
use think\Model;
class Common extends Model
{

    const SUPER_ADMIN_ID=1;//超级管理员ID
    protected $prefix;

    public function initialize(){
        $this->prefix=config('database.prefix');
    }

}