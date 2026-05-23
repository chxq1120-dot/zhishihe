<?php
namespace app\api\controller;

class Payorder extends Common
{
    public function initialize()
    {
    }

    public function qrcode()
    {
        $this->success('success', ['qrcode' => '']);
    }

    public function buy()
    {
        $this->success('success');
    }

    public function unify()
    {
        $this->success('success');
    }

    public function getCode()
    {
        $this->success('success');
    }

    public function profile()
    {
        $this->success('success');
    }

    public function oauthcallback()
    {
        $this->success('success');
    }
}
