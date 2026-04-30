<?php

namespace app\partner\behavior;
class PartnerLog
{
    public function run()
    {
        if (request()->isPost()) {
            \app\partner\model\PartnerLog::record();
        }
    }
}