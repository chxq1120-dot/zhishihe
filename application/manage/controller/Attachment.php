<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\lib\Storage;
use app\common\model\Attachment as AttachmentModel;
use app\common\model\AttachmentSort;
use oss\Qcloud;
use think\Db;
use oss\Alioss;
use app\common\model\Qiniu;
use zp\Tree;

class Attachment extends Common
{
    protected $alioss;

    protected $dataLimit='personal';
    public function initialize()
    {
        parent::initialize();
        $this->model=new AttachmentModel;
    }
    /**
     * 文件管理首页
     */
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 文件列表
     */
    public function getFileList()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type/d', 0);
            $sort = $this->request->param('sort/d', 0);
            $page = $this->request->param("page", 1);
            $search = $this->request->param("search",'');
            $limit = $this->request->param("limit", 20);
            $where = [];
            if (!empty($sort)) {
                $where[] = [['pid','=',$sort]];
            }
            if (!empty($search)) {
                $where[] = [['real_name','like','%'.$search.'%']];
            }
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                array_push($adminIds,0);
                $where[] = ['admin_id', 'in', $adminIds];
            }
            $list = $this->model->where($where)->page($page, $limit)->order('ctime desc')->select();
            foreach ($list as $k => $v) {
                if($v['up_type']==1){
                    if(stripos($v['satt_dir'],'http')===false){
                        $list[$k]['satt_dir']=$this->request->domain().$v['satt_dir'];
                    }
                }
            }
            $total = $this->model->where($where)->count();
            return json(['status' => 200, 'data' => $list, 'total' => $total]);
        }
    }

    /**
     * 删除文件
     */
    public function delFile()
    {
        try {
            if($this->request->isAjax()) {
                $id = $this->request->param('id/d', 0);
                if (empty($id)) {
                    return callback(400, '删除失败');
                }
                $attachment = $this->model->where('id', $id)->find();
                switch ($attachment->up_type) {
                    case 1:#本地
                        $result = @unlink('.'.$attachment->att_dir);
                        if (!$result) {
                            return callback(400, '删除失败');
                        }
                        break;
                    case 2:#阿里云
                        $oss = new Alioss();
                        $result = $oss->delObject($attachment->att_dir);
                        if ($result['status']!==200) {
                            return callback(400, $result['msg']);
                        }
                        break;
                    case 3:#腾讯云
                        $qcloud = new Qcloud();
                        $result = $qcloud->deleteObject($attachment->att_dir);
                        if ($result['status']!==200) {
                            return callback(400, $result['msg']);
                        }
                        break;
                    case 4:#七牛云
                        $qiniu = new Qiniu();
                        $result = $qiniu->deleteObject($attachment->att_dir);
                        if ($result['status']!==200) {
                            return callback(400, $result['msg']);
                        }
                        break;
                }
                $attachment->delete();
                return callback(200, '删除成功');
            }
        } catch (\Exception $e) {
            return callback(400, $e->getMessage());
        }
    }
    /**
     * 批量删除文件
     */
    public function delFiles()
    {
        try {
            if($this->request->isAjax()) {
                $ids = $this->request->param('ids/s' );
                if (empty($ids)) {
                    return callback(400, '请选择文件');
                }
                $ids = explode(',', $ids);
                $attachments = $this->model->where('id','in', $ids)->select();
                foreach ($attachments as $attachment) {
                    switch ($attachment->up_type) {
                        case 1:#本地
                            $result = @unlink('.'.$attachment->att_dir);
                            if (!$result) {
                                return callback(400, '删除失败');
                            }
                            break;
                        case 2:#阿里云
                            $oss = new Alioss();
                            $result = $oss->delObject($attachment->att_dir);
                            if ($result['status']!==200) {
                                return callback(400, $result['msg']);
                            }
                            break;
                        case 3:#腾讯云
                            $qcloud = new Qcloud();
                            $result = $qcloud->deleteObject($attachment->att_dir);
                            if ($result['status']!==200) {
                                return callback(400, $result['msg']);
                            }
                            break;
                        case 4:#七牛云
                            $qiniu = new Qiniu();
                            $result = $qiniu->deleteObject($attachment->att_dir);
                            if ($result['status']!==200) {
                                return callback(400, $result['msg']);
                            }
                            break;
                    }
                    $attachment->delete();
                }
                return callback(200, '删除成功');
            }
        } catch (\Exception $e) {
            return callback(400, $e->getMessage());
        }
    }
    /**
     * 添加分类
     */
    public function handleSort()
    {
        try {
            if ($this->request->isAjax()) {
                $pid = $this->request->param('pid/d', 0);
                $id = $this->request->param('id/d', 0);
                $name = $this->request->param('name');
                if (empty($name)) {
                    return callback(400, '请输入分类名称');
                }
                $sortModel = new AttachmentSort();
                if ($id) {
                    $sort = $sortModel->where('id', $id)->find();
                    if (!$sort) {
                        return callback(400, '修改失败');
                    }
                    $sort->name = $name;
                    $sort->ctime = time();
                    $result = $sort->save();
                    if (!$result) {
                        return callback(400, '修改失败');
                    }
                    return callback(200, '修改成功');
                } else {
                    $result = $sortModel->create(['pid' => $pid, 'name' => $name], true);
                    if (!$result) {
                        return callback(400, '创建失败');
                    }
                    return callback(200, '创建成功');
                }
            }
        }catch (\Exception $e) {
            return callback(400, $e->getMessage());
        }
    }

    /**
     * 删除分类
     */
    public function delSort()
    {
        try {
            if ($this->request->isAjax()) {
                Db::startTrans();
                $id = $this->request->param('id/d', 0);
                if (empty($id)) {
                    return callback(400, '删除失败');
                }
                $result = AttachmentSort::where('id', $id)->delete();
                if (!$result) {
                    return callback(400, '删除失败');
                }
                #更新删除分类所属的分类
                $this->model->where('pid', $id)->update(['pid'=>5,'ctime'=>time()]);
                Db::commit();
                return callback(200, '删除成功');
            }
        }catch (\Exception $e) {
            return callback(400, $e->getMessage());
        }
    }

    /**
     * 文件分类
     */
    public function getAttachSort()
    {
        if ($this->request->isAjax()) {
            $sort_list = [];
            $sortModel = new AttachmentSort();
            $sort_list = $sortModel->field('id,pid,name')->order('id asc')->select();
            if ($sort_list) {
                $sort_list = $sort_list->toArray();
                $tree = new Tree();
                $tree->init($sort_list, 'pid', '', 'children');
                $sortList = $tree->getTreeArray('pid');
            }
            return callback(200, '获取成功', '',$sortList);
        }
        return callback(400, '获取失败');
    }

    /**
     * 文件上传
     * @return array
     */
    public function upload()
    {
        $pid = request()->param('pid/d', 0);
        $is_thumb = request()->param('is_thumb/d', 0);
        $is_water = request()->param('is_water/d', 0);
        $storage = new Storage();
        list($result,$info)=$storage->upload($pid,$this->admin_uid,$is_thumb,$is_water);
        if(!$result){
            return callback(400, $info);
        }
        return callback(200, '上传成功', $info['url']);
    }
    /**
     * 文件下载
     * @return array
     */
    public function download()
    {
        $pid = request()->param('pid/d', 0);
        $is_thumb = request()->param('is_thumb/d', 0);
        $is_water = request()->param('is_water/d', 0);
        $url = request()->param('url/s','');
        if(empty($url)){
            return callback(400, '上传失败');
        }
        $storage = new Storage();
        list($result,$info)=$storage->download($pid,$this->admin_uid,$url,$is_thumb,$is_water);
        if(!$result){
            return callback(400, $info);
        }
        return callback(200, '上传成功', $info['url']);
    }
}