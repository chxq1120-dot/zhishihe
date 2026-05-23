<?php
namespace app\api\controller;

class Svip extends Common
{
    public function initialize()
    {
    }

    public function sviptype()
    {
        $list = [
            ['id' => 1, 'name' => '月卡', 'price' => 29.9, 'days' => 30, 'desc' => '月卡会员'],
            ['id' => 2, 'name' => '年卡', 'price' => 199, 'days' => 365, 'desc' => '年卡会员']
        ];
        $this->success('success', ['list' => $list]);
    }

    public function svipinfo()
    {
        $info = [
            'id' => 0, 'name' => '普通会员',
            'exp_time' => 0, 'is_vip' => 0
        ];
        $this->success('success', $info);
    }
}
