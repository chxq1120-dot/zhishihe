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
            'phone' => '', 'money' => '0.00', 'total_money' => '0.00', 'exp_time' => 0,
            'is_vip' => 0, 'r_nums' => 0, 'invite_nums' => 0, 'is_bind_mobile' => 0,
            'vid' => 0, 'pid' => 0, 'admin_id' => 1, 'status' => 1
        ];
        $this->success('success', $data);
    }

    public function userinfo()
    {
        $data = [
            'id' => 1, 'nickname' => '访客用户', 'avatar' => '',
            'phone' => '', 'money' => '0.00', 'total_money' => '0.00', 'exp_time' => 0,
            'is_vip' => 0, 'r_nums' => 0, 'invite_nums' => 0, 'is_bind_mobile' => 0,
            'vid' => 0, 'pid' => 0, 'admin_id' => 1, 'status' => 1,
            'svip' => null
        ];
        $this->success('success', $data);
    }

    public function mybuyres()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function mycoll()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function mytaskres()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function myOrderDetail()
    {
        $this->success('success', ['info' => []]);
    }

    public function teamlist()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function cashlist()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function incomeStat()
    {
        $this->success('success', ['total_money' => '0.00', 'today_money' => '0.00', 'wait_money' => '0.00']);
    }

    public function incomelist()
    {
        $this->success('success', ['list' => [], 'total' => 0]);
    }

    public function coll()
    {
        $this->success('success');
    }

    public function saveInfo()
    {
        $this->success('success');
    }

    public function cash()
    {
        $this->success('success');
    }

    public function subscribe()
    {
        $this->success('success');
    }

    public function sviptask()
    {
        $this->success('success', ['list' => []]);
    }

    public function obsviptask()
    {
        $this->success('success');
    }

    public function updateConfirm()
    {
        $this->success('success');
    }

    public function keeporder()
    {
        $this->success('success');
    }

    public function keepweborder()
    {
        $this->success('success');
    }

    public function keepttorder()
    {
        $this->success('success');
    }

    public function qxorder()
    {
        $this->success('success');
    }

    public function createPoster()
    {
        $this->success('success', ['imgurl' => '']);
    }

    public function spreadPoster()
    {
        $this->success('success', ['imgurl' => '']);
    }

    public function spreadH5Poster()
    {
        $this->success('success', ['imgurl' => '']);
    }

    public function getMobile()
    {
        $this->success('success', ['phone' => '']);
    }

    public function upload()
    {
        $this->success('success', ['url' => '']);
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
