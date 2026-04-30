<?php

namespace app\manage\controller;

use GuzzleHttp\Client;
use app\common\model\Admin;
use app\common\model\ResourceType;
use app\common\model\Resource;
use app\common\model\MarketLog;
use GuzzleHttp\Exception\RequestException;
use app\common\model\ShoppingCart;
use Endroid\QrCode\QrCode;
use think\facade\Db;
use think\facade\Session;
use zp\Tree;
use app\common\model\VideoCourse;
use app\common\model\AudioCourse;

/**
 * 资源市场
 */
class Market extends Common
{


    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取资源市场地址
     * @return [type] [description]
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * 主页
     * @return [type] [description]
     */
    public function index()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            $client = new Client();
            $response = $client->request('POST',
                $this->getApiUrl() . '/api/Resource/getlist',
                [
                    'form_params' => $data,
                    'verify' => false,
                    'timeout' => 30,
                ]
            );
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return json(['code' => 400, 'msg' => '请稍后再试']);
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] !== 200) {
                return json(['code' => 400, 'msg' => '请稍后再试1']);
            }
            return json(['code' => 0, 'data' => $info['data'], 'count' => $info['total']]);
        }
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Resource/getSort',
            [
                'form_params' => [],
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        $sortList = [];
        $smallList = [];
        if ($code == 200) {
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] == 200) {
                $tree = new Tree();
                $tree->init($info['data'], 'pid');
                $sortList = $tree->getTreeArray(0);
                $sid = $this->request->param('sid/d', 0);
                if ($sid) {
                    $smallList = $tree->getTreeArray($sid);
                }
            }
        }
        $num = ShoppingCart::count();
        $this->assign('sortList', $sortList);
        $this->assign('smallList', $smallList);
        $this->assign('num', $num);
        return view();
    }

    /**
     * 获取资源分类
     */
    public function sorts()
    {
        $data = input('param.');
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Resource/getSort',
            [
                'form_params' => $data,
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        if ($code !== 200) {
            return json(['code' => 400, 'msg' => '请稍后再试']);
        }
        $info = $response->getBody()->getContents();
        $info = json_decode($info, true);
        if ($info['code'] !== 200) {
            return json(['code' => 400, 'msg' => $info['msg']]);
        }
        $tree = new Tree();
        $tree->init($info['data'], 'pid');
        $datas = $tree->getTreeArray('pid');
        return json(['code' => 0, 'data' => $datas]);
    }

    /**
     * 详情
     * @return [type] [description]
     */
    public function info()
    {
        $data = input('param.');
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Resource/getInfo',
            [
                'form_params' => $data,
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        if ($code !== 200) {
            return json(['code' => 400, 'msg' => '请稍后再试']);
        }
        $info = $response->getBody()->getContents();
        $info = json_decode($info, true);
        if ($info['code'] !== 200) {
            return json(['code' => 400, 'msg' => $info['msg']]);
        }
        return view('', ['info' => $info['data']]);
    }

    /**
     * 申请入驻
     * @return [type] [description]
     */
    public function register()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            //注册来源回调
            $data['source'] = 2;
            $data['notify'] = request()->domain();
            $client = new Client();
            $response = $client->request('POST',
                $this->getApiUrl() . '/api/Login/register',
                [
                    'form_params' => $data,
                    'verify' => false,
                    'timeout' => 30,
                ]
            );
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return callback(400, '请稍后再试');
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] === 200) {
                $token = $info['data']['token'];
                $uid = $this->admin_uid;
                Admin::update(['api_token' => $token, 'id' => $uid]);
                $admin = Session::get('admin');
                $admin['api_token'] = $token;
                Session::set('admin', $admin);
                return callback(200, '入驻成功');
            } else {
                return callback(400, $info['msg']);
            }
        } else {
            return view();
        }
    }

    /**
     * 出售资源
     * @return [type] [description]
     */
    public function sell()
    {
        if (request()->isAjax()) {
            $ids = $this->request->param('ids');
            if (empty($ids)) {
                return callback(400, '未选择资源');
            }
            if (!$this->auth->api_token) {
                return callback(400, '未入驻资源市场');
            }
            $money = $this->request->param('money/f', 0);
            $resources = Resource::alias('a')->join('resource_info b','a.id=b.rid')->where('a.id', 'in', $ids)->field('a.*,b.free_content,b.content')->select()->toArray();
            $data = [];
            foreach ($resources as $k => $v) {
                $v['sort'] = ResourceType::field('b.name,b.thumb,b.id')->alias('a')
                    ->where('a.rid', $v['id'])->join('resource_sort b', 'a.sid=b.id')->select()->toArray();
                if ($v['is_buy'] == 0 && $v['is_sell'] != 1) {
                    if ($v['type'] == 4) {
                        $v['course'] = VideoCourse::where('rid', $v['id'])->select()->toArray();
                    }
                    if ($v['type'] == 5) {
                        $v['course'] = AudioCourse::where('rid', $v['id'])->select()->toArray();
                    }
                    $v['money'] = $money;
                    $data[] = $v;
                }

            }
            if (empty($data)) {
                return callback(400, '此资源不可出售');
            }
            try {
                $client = new Client();
                $response = $client->request('POST',
                    $this->getApiUrl() . '/api/Resource/subsell',
                    [
                        'json' => [
                            'data' => $data,
                            'token' => $this->auth->api_token
                        ],
                        'verify' => false,
                        'timeout' => 30,
                    ]
                );
            } catch (RequestException $e) {
                return response($e->getResponse()->getBody()->getContents());
            }
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return callback(400, '请稍后再试');
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] == 200) {
                foreach ($data as $v) {
                    Resource::where('id', $v['id'])->update(['is_sell' => 1]);
                }
                return callback(200, '提交成功');
            } else {
                return callback(400, $info['msg']);
            }
        }
    }

    /**
     * 消息列表
     * @return [type] [description]
     */
    public function news()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            $list = MarketLog::order('id desc')
                ->paginate(['list_rows' => $data['limit'], 'page' => $data['page']])
                ->toArray();
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total']]);
        }
        return view('msg');
    }

    /**
     * 购物车
     * @return [type] [description]
     */
    public function cart()
    {
        $list = ShoppingCart::all();
        $total = ShoppingCart::sum('money');
        if (empty($total)) {
            $total = 0;
        }
        $this->assign('total', $total);
        $this->assign('list', $list);
        return view();
    }

    /**
     * 批量购物车
     * @return [type] [description]
     */
    public function batchCart()
    {
        $list = input('post.cart/a');
        if (empty($list)) {
            return callback(400, '请选择资源后操作');
        }
        $data = [];
        foreach ($list as $k => $v) {
            $data[] = [
                'rid' => $v['id'],
                'title' => $v['title'],
                'money' => $v['money'],
                'thumb' => $v['thumb'],
            ];
        }
        $cartModel = new ShoppingCart();
        if ($cartModel->saveAll($data)) {
            return callback(200, '添加操作成功');
        } else {
            return callback(400, '添加操作失败');
        }
    }

    /**
     * 添加购物车
     * @return [type] [description]
     */
    public function addcart()
    {
        $data = input('param.');
        $arr['thumb'] = $data['data']['thumb'];
        $arr['title'] = $data['data']['title'];
        $arr['money'] = $data['data']['money'];
        $arr['rid'] = $data['data']['id'];
        if (ShoppingCart::create($arr)) {
            return callback(200, '添加成功');
        } else {
            return callback(400, '操作失败');
        }
    }

    /**
     * 删除
     * @return [type] [description]
     */
    public function delcart()
    {
        $ids = input('param.id');
        $cartModel = new ShoppingCart();
        if($ids=='o'){
            $result = \think\Db::name('shopping_cart')->delete(true);
        }else{
            $result = $cartModel::destroy($ids);
        }
        if ($result) {
            return callback(200, '操作成功');
        } else {
            return callback(400, '操作失败');
        }
    }

    /**
     * 支付
     * @return [type] [description]
     */
    public function pay()
    {
        $data = input('param.');
        if (request()->isAjax()) {
            if (isset($data['ids'])) { //直接购买
                $ids = $data['ids'];
            } elseif (isset($data['cart']) && $data['cart'] == 1) { //购物车购买
                $rids = ShoppingCart::column('rid');
                if (count($rids) > 1) {
                    $ids = implode(',', $rids);
                } elseif (count($rids) == 1) {
                    $ids = $rids[0];
                } else {
                    return callback(400, '市场资源异常请稍后再试');
                }
            } else {
                return callback(400, '市场资源异常请稍后再试');
            }
            try {
                //开始下单
                $client = new Client();
                $response = $client->request('POST',
                    $this->getApiUrl() . '/api/Order/PlaceOrder',
                    [
                        'form_params' => [
                            'token' => $this->auth->api_token,
                            'ids' => $ids,
                            'type' => $data['pay']
                        ],
                        'verify' => false,
                        'timeout' => 30,
                    ]
                );
            } catch (RequestException $e) {
                return response($e->getResponse()->getBody()->getContents());
            }
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return callback(400, '市场资源异常请稍后再试');
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] !== 200) {
                return callback(400, $info['msg']);
            } else {
                if ($info['msg'] == '购买成功') {
                    return callback(200, '购买成功', '', ['type' => $data['pay']]);
                }
                $qrcode = new QrCode($info['data']['url']);
                $imgbase64 = 'data:png;base64,' . chunk_split(base64_encode($qrcode->writeString()));
                return callback(200, 'success', '', ['imgbase64' => $imgbase64, 'type' => $data['pay'], 'ordno' => $info['data']['ordno'], 'api_url' => $this->getApiUrl() . '/api/Order/getOrderStatus', 'token' => $this->auth->api_token]);
            }
        } else {
            $client = new Client();
            $response = $client->request('POST',
                $this->getApiUrl() . '/api/User/userinfo',
                [
                    'form_params' => ['token' => $this->auth->api_token],
                    'verify' => false,
                    'timeout' => 30,
                ]
            );
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] === 200) {
                $this->assign('balance', $info['data']['balance']);
            } else {
                $this->assign('balance', '0.00');
            }
            if (isset($data['ids'])) {
                $totals = $data['money'];
            } elseif (isset($data['cart']) && $data['cart'] == 1) {
                $totals = ShoppingCart::sum('money');
                if (empty($totals)) {
                    $totals = '0.00';
                }
            }
            $this->assign('totals', $totals);
            return view();
        }
    }
}