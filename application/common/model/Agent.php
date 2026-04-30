<?php

namespace app\common\model;

use think\Model;
use think\Validate;

class Agent extends Model
{

    protected $table;
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'logtime_text'
    ];

    protected function initialize()
    {
        $this->table = config('database.prefix') . 'admin';
    }

    //搜索器
    public function searchGroupIdAttr($query, $value, $data)
    {
        $query->where('agent.group_id', 'in', $value);
    }

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    //获取器
    public function getLogtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logtime']) ? $data['logtime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
    }

    public function groups()
    {
        return $this->belongsTo('AuthGroup', 'group_id')->setEagerlyType(0);
    }

    public function agents()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0)->joinType('left');
    }

    /**
     * 创建代理分站
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createSite($params)
    {
        try {
            if (empty($params['username']) || empty($params['password'])) {
                return [false, '代理用户名或密码输入错误'];
            }
            $agent = $this->where('username', $params['username'])->find();
            if ($agent) {
                return [false, '代理用户名已经存在了'];
            }
            $svip = Svip::where('id', $params['svip_id'])->find();
            if (!$svip) {
                return [false, 'SVIP套餐不存在'];
            }
            $exp_time=time() + ($svip->days * 86400);

            $salt = mt_rand(111111, 999999);
            $data = [
                'username' => $params['username'],
                'password' => md5($params['password'] . $salt),
                'salt' => $salt,
                'mobile' => $params['mobile'],
                'admin_id' => $params['admin_id'],
                'min_pub'=>config('setting.agent_min_pub'),
                'dis_pub'=>config('setting.agent_dis_pub'),
                'cash_fee'=>config('setting.agent_cash_fee'),
                'min_cash'=>config('setting.agent_min_cash'),
                'min_take'=>config('setting.agent_min_take'),#分成比例
                'take_num'=>0,#扣量比例
                'group_id' => 2,
                'exp_time' =>$exp_time,
                'status' => 1,
                'ctime' => time()
            ];
            $agent = $this->create($data, true);
            if (!$agent) {
                return [false, '代理分站创建失败'];
            }
            #写入分站数据
            $site = AdminSite::where(['sub_prefix'=>$params['prefix'],'base_domain'=>$params['suffix']])->find();
            if ($site) {
                return [false, '代理分站创建失败，分站域名已经存在'];
            }
            $sites = [
                'admin_id' => $agent->id,
                'webname' => $params['webname'],
                'is_ssl' => 0,
                'sub_prefix' => $params['prefix'],
                'base_domain' => $params['suffix'],
                'mobile' => $params['mobile'],
                'ctime' => time()
            ];
            $site = AdminSite::create($sites, true);
            if (!$site) {
                return [false, '代理分站创建失败'];
            }
            #是否自动同步推广资源
            $agent_auto_spread=intval(config('setting.agent_auto_spread'));
            if ($agent_auto_spread==2) {
                $this->autoSpread($agent->id);
            }
            return [true, ['agent_id' => $agent->id]];
        } catch (\think\db\exception\DataNotFoundException $e) {
            return [false, $e->getMessage()];
        } catch (\think\db\exception\DbException $e) {
            return [false, $e->getMessage()];
        } catch (\think\exception\DbException $e) {
            return [false, $e->getMessage()];
        }
    }
    /**
     * 自动同步推广资源
     * @param $agent_id
     * @param $agent_min_pub
     * @param $agent_min_pub
     */
    public function autoSpread($agent_id,$agent_min_pub=0,$agent_dis_pub=0)
    {
        $min_pub=$agent_min_pub>0?$agent_min_pub:config('setting.agent_min_pub');
        $dis_pub=$agent_dis_pub>0?$agent_dis_pub:config('setting.agent_dis_pub');
        $admin_id =(new Admin())->getDefaultAdminId();
        $field = 'id,admin_id,title,thumb,link,ext_code,type,price,dis_price,desc,sales,level,invite,invite_num,exc_video,is_fenxiao,sell_set,sell_type,sell,sell_type2,sell2';
        $resources=Resource::where(['status'=>1,'admin_id'=>$admin_id])->field($field)->cursor();
        $agent_price_type = intval(config('setting.agent_price_type'));
        foreach ($resources as $resource) {
            $resource_arr = $resource->toArray();
            $resource_id = $resource_arr['id'];
            unset($resource_arr['id']);
            $resource_arr['rid'] = $resource_id;
            $resource_arr['price'] = $agent_price_type == 1 ? $min_pub : $resource->price;
            $resource_arr['dis_price'] = $agent_price_type == 1 ? $dis_pub : $resource->dis_price;
            $resource_arr['views'] = 0;
            $resource_arr['favs'] = 0;
            $resource_arr['admin_id'] = $agent_id;
            $resource_arr['utime'] = time();
            $resource_arr['ctime'] = time();
            $Spread = Spread::create($resource_arr, true);
            if (!$Spread) {
                continue;
            }
            $resourcetype = ResourceType::where('rid', $resource_id)->select();
            if ($resourcetype) {
                foreach ($resourcetype as $k => $v) {
                    SpreadType::create(['rid' => $Spread->id, 'sid' => $v['sid']]);
                }
            }
        }
        
    }
}