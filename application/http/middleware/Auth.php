<?php

namespace app\http\middleware;

use app\common\model\User;
use think\facade\Request;
class Auth
{
    public function handle($request, \Closure $next)
    {
        $token=$request->param('token');

        if(empty($token)){
            return json(['code'=>403,'msg'=>'请登录后再操作','data'=>[],'time'=>time()]);
        }
        $token=str_replace('Bearer ','',$token);
        #验证用户的Token
        $isToken=User::where(['token'=>$token,'status'=>1])->value('id');
        if(empty($isToken)){
            return json(['code'=>403,'msg'=>'请登录后再操作','data'=>[],'time'=>time()]);
        }
        $request->token=$token;
        $request->uid=$isToken;
        return $next($request);
    }
}
