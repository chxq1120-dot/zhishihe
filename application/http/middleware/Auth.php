<?php

namespace app\http\middleware;

use app\common\model\User;
use think\facade\Request;
class Auth
{
    public function handle($request, \Closure $next)
    {
        // 本地开发环境，直接跳过验证，模拟一个临时用户
        $request->token = 'temp_token';
        $request->uid = 1;
        return $next($request);
    }
}
