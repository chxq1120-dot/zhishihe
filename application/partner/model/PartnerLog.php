<?php

namespace app\partner\model;
use app\common\model\AuthRule;
use think\Model;

class PartnerLog extends Model
{
    //定义表
    protected $table;
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;
    //自定义日志标题
    protected static $title = '';
    //自定义日志内容
    protected static $content = '';
    protected function initialize()
    {
        $this->table = config('database.prefix') . 'admin_log';
    }
    public static function setTitle($title)
    {
        self::$title = $title;
    }

    public static function setContent($content)
    {
        self::$content = $content;
    }

    public static function record($title = '')
    {
        $admin_id = session('partner') ? session('partner')['id'] : 0;
        $username = session('partner') ? session('partner')['username'] : '未登录';
        $controllername = strtolower(request()->controller());
        $actionname = strtolower(request()->action());
        $content = self::$content;
        if (!$content) {
            $content = request()->param();
            foreach ($content as $k => $v) {
                if (is_string($v) && strlen($v) > 200 || stripos($k, 'password') !== false) {
                    unset($content[$k]);
                }
            }
        }
        $title = self::$title;
        if (!$title) {
            $title =self::getMenuName($controllername,  $actionname);
        }
        self::create([
            'remark'     => $username.'('.$title.')',
            'content'   => !is_scalar($content) ? json_encode($content) : $content,
            'url'       => substr(request()->url(), 0, 255),
            'ua'       => substr(request()->server('HTTP_USER_AGENT'), 0, 255),
            'uid'  => $admin_id,
            'ip'        => request()->ip(0,true)
        ]);
    }

    private static function getMenuName($controllername,  $actionname)
    {
        $href= $controllername.'/'.$actionname;
        $name= AuthRule::where('href',$href)->value('title');
        return $name;
    }
    public function admin()
    {
        return $this->belongsTo('Admin', 'uid')->setEagerlyType(0);
    }
}