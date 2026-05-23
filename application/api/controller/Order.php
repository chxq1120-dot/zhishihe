<?php
namespace app\api\controller;

class Order extends Common
{
    public function initialize()
    {
    }

    public function buyres()
    {
        $this->success('success');
    }

    public function buyvip()
    {
        $this->success('success');
    }

    public function ttbuyres()
    {
        $this->success('success');
    }

    public function ttbuyvip()
    {
        $this->success('success');
    }

    public function accbuyres()
    {
        $this->success('success');
    }

    public function webbuyres()
    {
        $this->success('success');
    }

    public function accbuyvip()
    {
        $this->success('success');
    }

    public function webbuyvip()
    {
        $this->success('success');
    }

    public function queryOrder()
    {
        $this->success('success', ['status' => 0]);
    }

    public function createPayOrder()
    {
        $this->success('success');
    }

    public function handleOfficial()
    {
        $this->success('success');
    }

    public function handleAlipay()
    {
        $this->success('success');
    }

    public function buyGroupIn()
    {
        $this->success('success');
    }
}
