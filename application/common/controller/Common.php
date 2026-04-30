<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\common\controller;

use app\common\model\Admin;
use org\File;
use think\Controller;
use think\facade\Env;

class Common extends Controller
{

    public function initialize()
    {
        $user = cookie('user');
        $this->assign('user', $user);
        $this->label_cms();
    }

    /**
     * 加载模版
     * @param $tpl string 模版路径
     * @return mixed
     */
    protected function tpl_fetch($tpl)
    {
        #加载自定义模板变量
        return $this->fetch($tpl);
    }

    /**
     * 加载系统基础模板变量
     */
    protected function label_cms()
    {
        $zhicms = $GLOBALS['config'];
        $this->assign(['zhicms' => $zhicms]);
    }
}