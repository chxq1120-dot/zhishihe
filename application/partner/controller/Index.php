<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

use app\common\model\Admin;
use app\common\model\Versions;
use GuzzleHttp\Client;
use think\Db;
use think\facade\Cache;

class Index extends Common
{
    protected $noNeedRight = ['logout', 'clear', 'main', 'nav', 'navbar'];
    public function index()
    {
        list($menus, $nav, $selected, $referer)=$this->auth->getSidebar();
        $this->assign('menus', $menus);
        return $this->fetch();
    }
    public function main()
    {
        $version = Versions::where('id', 1)->find();
        $this->assign('version', $version);
        return $this->fetch();
    }

    public function navbar()
    {
        return $this->fetch();
    }

    public function nav()
    {
        return $this->fetch();
    }

    public function clear()
    {
        #删除缓存目录
        $runPath =  env('runtime_path');
        //清除缓存
        Cache::clear();
        //清除缓存文件
        $result = removeDir($runPath);
        if($result){
            return callback(200, '缓存清除成功', createUrl('index/index'));
        }else{
            return callback(400, '缓存清除失败', createUrl('index/index'));
        }
    }

    /**
     * 获取统计
     */
    public function statinfo()
    {
        if($this->request->isAjax()){
            $maps = ['admin_id' => $this->admin_uid];
            $where = ['is_kl' => 0];
            #总访问量
            $total_visitor = \app\common\model\SpreadView::where($maps)->sum('num');
            #今日访问量
            $today_visitor = \app\common\model\SpreadView::where($maps)->whereTime('ctime', 'today')->sum('num');
            #昨日访问量
            $yes_visitor = \app\common\model\SpreadView::where($maps)->whereTime('ctime', 'yesterday')->sum('num');
            #本周访问量
            $week_visitor = \app\common\model\SpreadView::where($maps)->whereTime('ctime', 'week')->sum('num');
            #本月访问量
            $month_visitor = \app\common\model\SpreadView::where($maps)->whereTime('ctime', 'month')->sum('num');
            #上月访问量
            $last_month_visitor = \app\common\model\SpreadView::where($maps)->whereTime('ctime', 'last month')->sum('num');

            #用户量
            $total_user = \app\common\model\User::where($maps)->count();
            #今日用户量
            $today_user = \app\common\model\User::where($maps)->whereTime('ctime', 'today')->count();
            #昨日用户量
            $yes_user = \app\common\model\User::where($maps)->whereTime('ctime', 'yesterday')->count();
            #本周用户量
            $week_user = \app\common\model\User::where($maps)->whereTime('ctime', 'week')->count();
            #本月用户量
            $month_user = \app\common\model\User::where($maps)->whereTime('ctime', 'month')->count();
            #上月用户量
            $last_month_user = \app\common\model\User::where($maps)->whereTime('ctime', 'last month')->count();

            #总订单数
            $total_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->count();
            #今日订单数
            $today_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'today')->count();
            #昨日订单数
            $yes_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'yesterday')->count();
            #本周订单数
            $week_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'week')->count();
            #本月订单数
            $month_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'month')->count();
            #上月订单数
            $last_month_order = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'last month')->count();


            #总销售额
            $total_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->sum('money');
            #今日销售额
            $today_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'today')->sum('money');
            #昨日销售额
            $yes_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'yesterday')->sum('money');
            #本周销售额
            $week_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'week')->sum('money');
            #本月销售额
            $month_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'month')->sum('money');
            #本月销售额
            $last_month_money = \app\common\model\Order::where($maps)->where($where)->where('status',1)->whereTime('ctime', 'last month')->sum('money');

            return callback(200, 'success', '', [
                'total_visitor' => $total_visitor,
                'today_visitor' => $today_visitor,
                'yes_visitor' => $yes_visitor,
                'week_visitor' => $week_visitor,
                'month_visitor' => $month_visitor,
                'last_month_visitor' => $last_month_visitor,
                'total_user' => $total_user,
                'today_user' => $today_user,
                'yes_user' => $yes_user,
                'week_user' => $week_user,
                'month_user' => $month_user,
                'last_month_user' => $last_month_user,
                'total_order' => $total_order,
                'today_order' => $today_order,
                'yes_order' => $yes_order,
                'week_order' => $week_order,
                'month_order' => $month_order,
                'last_month_order' => $last_month_order,
                'total_money' => $total_money,
                'today_money' => $today_money,
                'yes_money' => $yes_money,
                'week_money' => $week_money,
                'month_money' => $month_money,
                'last_month_money' => $last_month_money,
            ]);
        }
    }

    public function dataView()
    {
        #访问量
        $days = $this->get7day();
        $visitor = [];
        foreach ($days as $day) {
            $nums = \app\common\model\SpreadView::where(['admin_id' => $this->admin_uid])
                ->whereTime('ctime', [$day . ' 00:00:00', $day . ' 23:59:59'])->sum('num');
            if (empty($nums)) {
                $nums = 0;
            }
            $visitor[] = $nums;
        }
        #用户量
        $useradd = [];
        foreach ($days as $day) {
            $nums = \app\common\model\User::where(['admin_id' => $this->admin_uid])
                ->whereTime('ctime', [$day . ' 00:00:00', $day . ' 23:59:59'])->count();
            if (empty($nums)) {
                $nums = 0;
            }
            $useradd[] = $nums;
        }
        #订单数量及订单金额
        $order_list = [];
        $order_money = [];
        foreach ($days as $day) {
            $maps = [$day.' 00:00:00', $day.' 23:59:59'];
            $nums = \app\common\model\Order::where(['admin_id' => $this->admin_uid,'status'=>1])
                ->whereTime('ctime', $maps)->count();
            $moneys = \app\common\model\Order::where(['admin_id' => $this->admin_uid,'status'=>1])
                ->whereTime('ctime', $maps)->sum('money');

            $order_list[] = $nums;
            $order_money[] = $moneys;
        }
        return callback(200, 'success', '', [
            'date_list' => $days,
            'view_list' => $visitor,
            'user_list' => $useradd,
            'order_list' => $order_list,
            'order_money' => $order_money
        ]);
    }

    /**
     * 获取最近七天
     */
    protected function get7day()
    {
        $days = [];
        for ($i = 0; $i < 14; $i++) {
            array_unshift($days, date('Y-m-d', strtotime('-' . $i . ' day')));
        }
        return $days;
    }

    #退出登陆
    public function logout()
    {
        $this->auth->logout();
        $this->redirect(createUrl('login/index'));
    }

}
