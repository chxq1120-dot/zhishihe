<?php

namespace app\http\middleware;

use think\facade\Cache;

class Throttle
{
    public function handle($request, \Closure $next)
    {
        $controller = $request->controller();
        $action = $request->action();
        $ip = $request->ip(0, 1);
        $key = md5($ip . '_' . $controller . '_' . $action);
        $limit = config('redis.limit') ?: 60;
        $count = Cache::get($key, 0);
        if ($count > $limit) {
            return json(['code' => 403, 'msg' => '请稍后...', 'data' => [], 'time' => time()]);
        }
        if ($count > 0) {
            Cache::inc($key);
        } else {
            Cache::set($key, 1, 60);
        }
        return $next($request);
    }
}
