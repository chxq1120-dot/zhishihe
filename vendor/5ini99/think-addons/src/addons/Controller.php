<?php
// +----------------------------------------------------------------------
// | thinkphp5 Addons [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zzstudio.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Byron Sampson <xiaobo.sun@qq.com>
// +----------------------------------------------------------------------
namespace think\addons;

use think\Request;
use think\Config;
use think\Loader;

/**
 * 插件基类控制器
 * Class Controller
 * @package think\addons
 */
class Controller extends \think\Controller
{
    // 当前插件操作
    protected $addon = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;
    // 模板配置信息
    protected $config = [
        'type' => 'Think',
        'view_path' => '',
        'view_suffix' => 'html',
        'strip_space' => true,
        'view_depr' => DIRECTORY_SEPARATOR,
        'tpl_begin' => '{',
        'tpl_end' => '}',
        'taglib_begin' => '{',
        'taglib_end' => '}',
    ];

    /**
     * 架构函数
     * @param Request $request Request对象
     * @access public
     */
    public function __construct($request = null)
    {
        // 生成request对象
        $this->request = is_null($request) ? request() : $request;
        // 初始化配置信息
        $this->config = config('template.') ?: $this->config;
        // 处理路由参数
        $path = $this->request->path();

        $url = explode('/',$path);
        $param = explode('-', $url[1]);

        // 是否自动转换控制器和操作名
        $convert = config('app.url_convert');
        // 格式化路由的插件位置
        $this->action = $convert ? strtolower(array_pop($param)) : array_pop($param);

        $this->controller = $convert ? strtolower(array_pop($param)) : array_pop($param);

        $this->addon = $convert ? strtolower(array_pop($param)) : array_pop($param);

        $addonName        = Loader::parseName($this->addon, 1);
        // 生成view_path
        $view_path = $this->config['view_path'] ?'view': 'view';
        // 重置配置
        config('template.view_path', ADDON_PATH . $addonName . DIRECTORY_SEPARATOR . $view_path . DIRECTORY_SEPARATOR);
        parent::__construct($request);

    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array $vars 模板输出变量
     * @param array $replace 模板替换
     * @param array $config 模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $controller = Loader::parseName($this->controller);
        if ('think' == strtolower($this->config['type']) && $controller && 0 !== strpos($template, '/')) {
            $depr = $this->config['view_depr'];
            $template = str_replace(['/', ':'], $depr, $template);
            if ('' == $template) {
                // 如果模板文件名为空 按照默认规则定位
                $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $this->action;
            } elseif (false === strpos($template, $depr)) {
                $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
            }
        }
        return parent::fetch($template, $vars, $replace, $config);
    }
}
