<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace app\manage\controller;
use think\facade\Env;
use zp\FormList;
use app\common\model\Setting as SettingModel;
use app\common\model\Config;
class Setting extends Common
{
    protected $groups,$config;
    public function initialize(){
        parent::initialize();
        $this->groups=[];
        $group=Config::where('name','group')->value('default');
        if(!empty($group)){
            $options=explode("\n",$group);
            foreach($options as $option){
                if(stripos($option,'|')!==false){
                    $option=explode('|',$option);
                    if($option[1]!=='system'){
                        $this->groups[trim($option[1])]=trim($option[0]);
                    }
                }
            }
        }
    }
    //站点设置
    public function index(){
        $setting=new SettingModel();
        if(request()->isPost()){
            $datas = input('post.');
            foreach($datas as $k=>$v){
                list($res,$msg)=$setting->setValue($k,$v);
                if(!$res){
                    return callback(400,$msg);
                }
                if($k=='client_key'){
                    $this->saveCert('apiclient_key.pem',$v);
                }
                if($k=='client_cert'){
                    $this->saveCert('apiclient_cert.pem',$v);
                }
            }
            cache('setting',null);
            return callback(200,'设置保存成功', createUrl('setting/index'));
        }
        #获取配置项
        foreach($this->groups as $k=>$v){
            $config[$k]=Config::where(['group'=>$k,'status'=>1])->order('indexid asc')->select()->toArray();
        }
        #获取已设置数据
        $data=$setting->getAll();
        $form=new FormList($data);
        $this->assign('groups',$this->groups);
        $this->assign('form',$form);
        $this->assign('config',$config);
        $this->assign('setting',$data);
        return $this->fetch();
    }
    /**
     * 保存证书
     */
    protected function saveCert($name,$content){
        $path=Env::get('root_path').'public/cert/'.$name;
        file_put_contents($path,$content);
        return true;
    }

    /**
     * 上传存储设置
     * @return mixed
     */
    public function storage()
    {
        if (request()->isPost()) {
            $datas = input('post.');
            $setting=new SettingModel();
            if(empty($datas['upload_thumb_open'])){
                $datas['upload_thumb_open']=0;
            }
            if(empty($datas['upload_water_open'])){
                $datas['upload_water_open']=0;
            }
            foreach($datas as $k=>$v){
                list($res,$msg)=$setting->setValue($k,$v);
                if(!$res){
                    return callback(400,$msg);
                }
            }
            $setting->getAll();
            cache('setting',null);
            return callback(200,'设置保存成功');
        }
        return $this->fetch();
    }
}
