<?php

namespace app\partner\behavior;
class AdminLog
{
    public function run()
    {
        if (request()->isPost()) {
            \app\partner\model\PartnerLog::record();
        }
    }
}
