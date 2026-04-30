<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\common\model\Links as linksModel;
class Links extends Common
{
    protected $searchFields='title';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='links';

    public function initialize(){
        parent::initialize();
        $this->model=new linksModel();
    }

}