<?php
namespace app\api\controller;
use app\common\model\MarketLog;
use app\common\model\Admin;
use app\api\validate\Check;
use app\common\model\Resource;
use app\common\model\ResourceSort;
use app\common\model\ResourceType;
use app\common\model\ShoppingCart;
use think\Db;
use app\common\model\VideoCourse;
use app\common\model\AudioCourse;
use app\common\model\ResourceInfo;

class Market extends Common{
	/**
	 * 审核回调
	 * @return [type] [description]
	 */
	public function shnotify(){
		$data=input('param.');
		$validate = new Check;
        if (!$validate->scene('Market.shnotify')->check($data)) {
            $this->error($validate->getError());
        }
        $user=Admin::where('api_token',$data['token'])->find();
        if(!$user){
        	$this->error('验证失败');
        }
        $info=Resource::where('id',$data['id'])->find();
        if($data['is_review']==-1){
        	$info->is_sell=-1;
        	$info->save();
        	$arr['title']='审核拒绝';
        }else{

        	$arr['title']='审核通过';
        }
        $arr['content']=$data['reply'].'('.$info['title'].')';
        $this->writelog($arr['title'],$arr['content']);
	}
    /**
     * 购买资源回调
     * @return [type] [description]
     */
    public function handlebuy(){
        $data=input('param.');
        $validate = new Check;
        if (!$validate->scene('Market.handlebuy')->check($data)) {
            $this->error($validate->getError());
        }
        $user=Admin::where('api_token',$data['token'])->find();
        if(!$user){
            $this->error('验证失败');
        }
        $admin_id=(new Admin())->getDefaultAdminId();
        foreach ($data['data'] as $v){
            $value=$v;
            $value['is_buy']=1;
            $value['admin_id']=$admin_id;
            unset($value['ctime']);
            unset($value['sort']);
            unset($value['uid']);
            unset($value['id']);
            $r=Resource::create($value,true);
            //处理资源详情
            ResourceInfo::create([
                'rid'=>$r->id,
                'free_content'=>$value['free_content'],
                'content'=>$value['content']
            ],true);
            //处理分类
            foreach ($v['sort'] as $val){
                $sort=ResourceSort::where('name',$val['name'])->find();
                if($sort){
                    $type=[
                        'rid'=>$r->id,
                        'sid'=>$sort->id
                    ];
                }else{
                    $s=ResourceSort::create(['name'=>$val['name'],'thumb'=>$val['thumb']]);
                    $type=[
                        'rid'=>$r->id,
                        'sid'=>$s->id,
                    ];
                }               
                ResourceType::create($type);
            }
            $this->writelog('购买资源','购买资源('.$v['title'].')');
             //课程处理
            if($v['type']==4 || $v['type']==5){
                if ($v['type']==4){
                    $db=new VideoCourse;
                }
                if($v['type']==5){
                    $db=new AudioCourse;
                }
                foreach ($v['course'] as $val){
                    $course=[
                        'title'=>$val['title'],
                        'thumb'=>$val['thumb'],
                        'url'=>$val['url'],
                        'desc'=>$val['desc'],
                        'rid'=>$r->id,
                        'times'=>$val['times'],
                        'is_try'=>$val['is_try'],
                        'indexid'=>$val['indexid']
                    ];
                    $db->create($course);
                }
            }
        }
        Db::query("truncate table `zp_shopping_cart`;");
    }
    /**
     * 写入日志
     * @param  [type] $title   [description]
     * @param  [type] $content [description]
     * @return [type]          [description]
     */
    public function writelog($title,$content){
        MarketLog::create(['title'=>$title,'content'=>$content]);
    }
}