<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2020/6/5
 * Time: 22:21
 */

namespace app\common\behavior;

use think\facade\Config;
use think\facade\View;
use think\Request;

class ViewInit
{
    public function run(Request $request, $params)
    {
        $view_theme = Config::get('template.view_theme');
        $GLOBALS['config'] = Config::get('setting.');
        $view_path = Config::get('template.view_path');
        if ($request->module() == 'index') {
            isMobile() ? $client_path = 'wap' : $client_path = 'pc';
            $view_path = $view_path . $view_theme . '/';
            $GLOBALS['config']['client_path'] = $client_path;
            $GLOBALS['config']['tpl_path'] = $view_path;
            View::config('view_path', $view_path);
        }
    }
}