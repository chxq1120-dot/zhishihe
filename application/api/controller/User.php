<?php
namespace app\api\controller;

class User extends Common
{
    public function initialize()
    {
    }

    public function index()
    {
        $data = [
            'id' => 1, 'nickname' => '访客用户', 'avatar' => '',
            'phone' => '', 'money' => 0, 'total_money' => 0, 'exp_time' => 0,
            'is_vip' => 0, 'r_nums' => 0, 'invite_nums' => 0, 'is_bind_mobile' => 0
        ];
        $this->success('success', $data);
    }

    public function login()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function smslogin()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function weblogin()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function register()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function autoregister()
    {
        $this->success('success', ['token' => 'temp_token']);
    }
}
