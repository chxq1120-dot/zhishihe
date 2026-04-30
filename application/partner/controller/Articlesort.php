<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\partner\controller;
use app\common\model\ArticleSort as sortModel;
class Articlesort extends Common{

    protected $searchFields='name';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='articlesort';

    public function initialize(){
        parent::initialize();
        $this->model=new sortModel();
    }
}