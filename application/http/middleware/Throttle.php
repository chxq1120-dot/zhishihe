<?php

namespace app\http\middleware;

class Throttle
{
    public function handle($request, \Closure $next)
    {
        $controller = $request->controller();
        $action = $request->action();
        $ip = $request->ip(0, 1);
        $key = md5($ip . '_' . $controller . '_' . $action);
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.port'));
        if (!empty(config('redis.auth'))) {
            $redis->auth(config('redis.auth'));
        }
        $passed = $redis->exists($key);
        if ($passed) {
            $redis->incr($key);
            $count = $redis->get($key);
            if ($count > config('redis.limit')) {
                return json(['code' => 403, 'msg' => '请稍后...', 'data' => [], 'time' => time()]);
            }
        } else {
            $redis->incr($key);
            $redis->expire($key, 60);
        }
        return $next($request);
    }
}
