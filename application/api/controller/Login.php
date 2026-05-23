<?php
namespace app\api\controller;

class Login extends Common
{
    public function initialize()
    {
    }

    public function subcode()
    {
        $this->success('success', ['token' => 'temp_token', 'openid' => 'temp_openid']);
    }

    public function login()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function loginApp()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function autoregister()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function register()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function oauth()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function weblogin()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function resetpwd()
    {
        $this->success('success');
    }

    public function toutiaologin()
    {
        $this->success('success', ['token' => 'temp_token']);
    }

    public function smsLogin()
    {
        $this->success('success', ['token' => 'temp_token']);
    }
}
