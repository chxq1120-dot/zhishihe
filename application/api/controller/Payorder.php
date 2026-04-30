<?php
namespace app\api\controller;
use app\common\model\Order;
use app\api\validate\Check;
use app\common\model\Spread;
use app\common\model\User;
use app\common\model\Privilege;
use Naixiaoxin\ThinkWechat\Facade;
use app\common\model\Svip;
use Endroid\QrCode\QrCode;
use think\Controller;

class Payorder extends Controller
{
	/**
	 * 
	 * @return [type] [description]
	 */
	public function buy()
    {
    	$data = input('param.');
    	$assign['data']=$data;
    	if($data['type']==1){
			$assign['info']=Spread::where('id', $data['id'])->find();
    		return view('buyres',$assign);
		}elseif($data['type']==2){
			$assign['info']=Svip::where('id',$data['id'])->find();
    	    return view('buyvip',$assign);
		}    
    }
    //下单
    public function unify(){
    	$data = input('param.');
    	if(request()->isAjax()){
    		$validate = new Check;
	        if (!$validate->scene('Payorder.buy')->check($data)) {
	        	return callback(400,$validate->getError());
	        }
	        $userinfo = User::where('token', $data['token'])->find();
	        if(!$userinfo){
	        	return callback(400,'用户错误');
	        }
	        $uid=$userinfo->id;
	        if($data['type']==1){ //购买资源
	        	$res = Spread::where('id', $data['id'])->find();
		        if (!$res) {
		        	return callback(400,'资源不存在');
		        }
		        if ($res->status == 0) {
		        	return callback(400,'资源已下架');
		        }
		        $arr['ordno'] = date('YmdHis') . mt_rand(10000, 99999);
		        $arr['money'] = $res->price;
		        //判断分销
		        if ($res->is_fenxiao) {
		            //判断合伙人
		            if (!empty($userinfo->pid)) {
		                $pinfo = User::where(['id' => $userinfo->pid, 'status' => 1])->find();
		                if ($pinfo && (time() < $pinfo->exp_time)) {
		                    //获取邀请人特权
		                    $rule = Privilege::handleUserAuth($pinfo->vid, '', 5);
		                    if ($rule) {
		                        //合伙人设置
		                        if ($res->sell_set == 1) {
		                            $arr['hr_money'] = bcmul($rule, $arr['money'], 2);
		                        } else {
		                            //商品设置
		                            if ($res->sell_type == 1) {//是否系统赠送
		                                $arr['hr_money'] = bcmul($res->sell, $arr['money'], 2);
		                            } else {
		                                $arr['hr_money'] = $res->sell;
		                            }
		                        }
		                    }
		                }
		            }
		        }

		        $arr['admin_id'] = $userinfo->admin_id;
		        $arr['pid'] = $userinfo->pid;
		        $arr['uid'] = $uid;
		        $arr['nickname'] = $userinfo->nickname;
		        $arr['type'] = 1;
		        $arr['rid'] = $data['id'];
		        $arr['pay_type'] = 1;
		        if (!(new Order)->save($arr)) {
		        	return callback(400,'购买失败');
		        }
		        $openid=session($data['token']);
		        $order = [
		            'body' => '购买资源',
		            'out_trade_no' => $arr['ordno'],
		            'total_fee' => $arr['money'] * 100,
		            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
		            'openid' => $openid,
		        ];
		        $payment = Facade::payment('official_account'); // 微信支付
		        $result = $payment->order->unify($order);
		        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
		        	$jssdk = $payment->jssdk;
		        	$json = $jssdk->bridgeConfig($result['prepay_id']);

		        	return callback(200,'success',null,$json);
		        } else {
		        	return callback(400,'下单失败');
		        }
	        }elseif($data['type']==2){ //购买VIP
	        	$res = Svip::where('id', $data['id'])->find();
	            if (!$res) {
	            	return callback(400,'会员类型不存在');
	            }
	            $arr['ordno'] = date('YmdHis') . mt_rand(10000, 99999);
	            $arr['money'] = $res->price;

	            if (!empty($userinfo->pid)) {//判断合伙人
	                $pinfo = User::where('id', $userinfo->pid)->find();
	                if ($pinfo) {
	                    if (time() < $pinfo->exp_time) {
	                        $rule = Privilege::handleUserAuth($pinfo->vid, '', 5);
	                        if ($rule) {
	                            $arr['hr_money'] = bcmul($rule, $arr['money'], 2);
	                        }
	                    }
	                }
	            }
	            $arr['admin_id'] = $userinfo->admin_id;
	            $arr['pid'] = $userinfo->pid;
	            $arr['uid'] = $uid;
	            $arr['nickname'] = $userinfo->nickname;
	            $arr['type'] = 2;
	            $arr['vid'] = $data['id'];
	            $arr['pay_type'] = 1;
	            if (!(new Order)->save($arr)) {
	                return callback(400,'购买失败');
	            }
	            $openid=session($data['token']);
	            $order = [
	                'body' => '购买会员',
	                'out_trade_no' => $arr['ordno'],
	                'total_fee' => $arr['money'] * 100,
	                'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
	                'openid' => $openid,
	            ];
	            $payment = Facade::payment('official_account'); // 微信支付
	            $result = $payment->order->unify($order);
	            if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
	            	$jssdk = $payment->jssdk;
		        	$json = $jssdk->bridgeConfig($result['prepay_id']);
		        	return callback(200,'success',null,$json);
	            } else {
	                return callback(400,'下单失败');
	            }
	        }
    	}
    }
    //获取二维码
    public function getCode(){
    	$data = input('param.');
    	$validate = new Check;
        if (!$validate->scene('Payorder.buy')->check($data)) {
        	return callback(400,$validate->getError());
        }
    	$url=request()->domain().'/api/Payorder/profile?id='.$data['id'].'&token='.$data['token'].'&type='.$data['type'];
    	$qrcode=new QrCode($url);
    	return response($qrcode->writeString())->contentType('image/png');    	
    }
    //显示二维码地址
    public function qrcode(){
        $data = input('param.');
        return json(
            [
                'code'=>200,
                'msg'=>'success',
                'data'=>[
                    'qrcode'=>$this->request->domain().'/api/payorder/getCode?id='.$data['id'].'&token='.$data['token'].'&type='.$data['type']
                ]
            ]
        );
    }
    /**
     * 发起授权
     * @return [type] [description]
     */
    public function profile(){
    	$data=input('param.');
    	if(!session($data['token'])){
    		$app = Facade::officialAccount();
	        $oauth = $app->oauth;
	        $redirectUrl = $oauth->redirect();
	        cookie('token',$data);
	        return $redirectUrl;
    	}else{
    		$url=request()->domain().'/api/Payorder/buy?id='.$data['id'].'&token='.$data['token'].'&type='.$data['type'];
    		return redirect($url);
    	}
    	
    }
    /**
     * [oauthcallback description]
     * @return [type] [description]
     */
    public function oauthcallback(){
    	$data=cookie('token');
    	$app = Facade::officialAccount();
        $oauth = $app->oauth;
        $user = $oauth->user();
        $user=$user->toArray();
        //var_dump($user);die();
        session($data['token'],$user['id']);
        $url=request()->domain().'/api/Payorder/buy?id='.$data['id'].'&token='.$data['token'].'&type='.$data['type'];
		return redirect($url);
    }
}