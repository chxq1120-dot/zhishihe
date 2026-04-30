<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

use app\common\lib\Toutiao;
use app\common\model\Admin;
use app\common\model\AdminSite;
use app\common\model\PosterConfig;
use app\common\model\Qiniu;
use app\common\model\ResourceLevel;
use app\common\model\{
    Spread as spreadModel,
    ResourceSort,
    SpreadType,
    Groups,
    SpreadGroup,
    SpreadJump,
    Jump,
};
use app\common\model\WechatKey;
use app\common\model\WechatRece;
use app\common\model\WechatSub;
use EasyWeChat\Kernel\Exceptions\HttpException;
use oss\Alioss;
use oss\Qcloud;
use EasyWeChat\Kernel\Support\File;
use PosterMaker\PosterMaker;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use Naixiaoxin\ThinkWechat\Facade;
use GuzzleHttp\Client;
use think\exception\ValidateException;
use think\facade\Env;
use zp\Tree;

class Wechat extends Common
{

    public function initialize()
    {
        parent::initialize();
        $this->model = new spreadModel();
    }

    /**
     * 微信公众号菜单
     */
    public function menus()
    {

        if ($this->request->isPost()) {
            try {
                $act = $this->request->param('act/s');
                $app = Facade::officialAccount();
                switch (strtoupper($act)) {
                    case 'MENULIST':
                        $list = $app->menu->list();
                        if (isset($list['errcode'])) {
                            return callback(304, $list['errmsg']);
                        }
                        return callback(200, 'success', '', ['menulist' => $list['menu']]);
                        break;
                    case 'MEDIALIST':
                        $type = $this->request->param('type', 'image');
                        if (empty($type)) {
                            $type = 'image';
                        }
                        $page = $this->request->param('page/d', 1);
                        $limit = $this->request->param('limit/d', 20);
                        $offset = ($page - 1) * $limit;
                        $result = $app->material->list($type, $offset, $limit);
                        if (isset($result['errcode'])) {
                            return callback(404, $result['errmsg']);
                        }
                        return callback(200, 'success', '', $result);
                        break;
                    case 'DELMENU':
                        $menuId = $this->request->param('menuId');
                        if (empty($menuId)) {
                            return callback(404, '菜单ID不能为空');
                        }
                        $app->menu->delete($menuId);
                        return callback(200, '删除成功');
                        break;
                    case 'SAVEMENU':
                        $menus = $this->request->param('menus');
                        if (empty($menus)) {
                            return callback(404, '请设置好菜单再保存');
                        }
                        $result = $app->menu->create($menus);
                        if ($result['errcode'] !== 0) {
                            return callback(404, $result['errmsg']);
                        }
                        return callback(200, '菜单保存成功');
                        break;
                }
            } catch (HttpException $e) {
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                return callback(404, $e->getMessage());
            }
        }
        return $this->view->fetch('wechat/menus');
    }

    /**
     * 关键字回复列表
     */
    public function words()
    {
        $this->model = new WechatKey();
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch('wechat/words');
    }

    /**
     * 添加关键字规则
     */
    public function addwords()
    {
        $this->model = new WechatKey();
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $this->dataLimit = false;
            $this->modelValidate = false;
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    if (empty($params['tags'])) {
                        return callback(404, '请输入关键字');
                    }
                    if ($params['type'] == 'text' && empty($params['content'])) {
                        return callback(404, '请输入消息内容');
                    }
                    if ($params['type'] !== 'text' && empty($params['content'])) {
                        return callback(404, '请上传素材内容');
                    }
                    if (is_array($params['tags'])) {
                        $params['name'] = implode(',', $params['tags']);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '添加成功');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        return $this->view->fetch('wechat/addwords');
    }

    /**
     * 更新单个字段值
     */
    public function setUpw($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            $this->model = new WechatKey();
            if ($this->request->has('params') && !empty($this->request->post("params"))) {
                $values = json_decode($this->request->post("params"), true);
                $count = 0;
                Db::startTrans();
                try {
                    $pk = $this->model->getPk();
                    $list = $this->model->where($pk, 'in', $ids)->select();
                    foreach ($list as $item) {
                        $count += $item->allowField(true)->isUpdate(true)->save($values);
                    }
                    Db::commit();
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($count) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '更新失败');
                }
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 上传公众号素材
     */
    public function uploadMedia()
    {
        try {
            if ($this->request->isPost()) {
                $file = request()->file('file');
                $type = $this->request->param('type', 'image');
                switch ($type) {
                    case 'image':
                        $ext = 'jpg,png,gif,jpeg,bmp';
                        break;
                    case 'voice':
                        $ext = 'mp3,wma,wav,amr,m4a';
                        break;
                    case 'video':
                        $ext = 'mp4,flv,f4v,webm,m4v,mov,3gp,3g2';
                        break;
                }
                $info = $file->validate(['size' => 5000 * 1024 * 1024, 'ext' => $ext])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
                if ($info) {
                    $fileName = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $info->getSaveName();
                    $app = Facade::officialAccount();
                    switch ($type) {
                        case 'image':
                            $result = $app->material->uploadImage($fileName);
                            break;
                        case 'voice':
                            $result = $app->material->uploadVoice($fileName);
                            break;
                        case 'video':
                            $result = $app->material->uploadVideo($fileName,'视频标题','视频描述');
                            break;
                    }
                    if (isset($result['errcode'])) {
                        $this->error($result['errmsg']);
                    }
                    unset($info);
                    @unlink($fileName);
                    $this->success('上传成功', '', $result);
                } else {
                    $this->error($file->getError());
                }
            }
        } catch (HttpException $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 编辑关键字规则
     */
    public function editwords($ids = 0)
    {
        $this->model = new WechatKey();
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $this->dataLimit = false;
            $this->modelValidate = false;
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    if (empty($params['tags'])) {
                        return callback(404, '请输入关键字');
                    }
                    if ($params['type'] == 'text' && empty($params['content'])) {
                        return callback(404, '请输入消息内容');
                    }
                    if ($params['type'] !== 'text' && empty($params['content'])) {
                        return callback(404, '请上传素材内容');
                    }
                    if (is_array($params['tags'])) {
                        $params['name'] = implode(',', $params['tags']);
                    }
                    $params['utime'] = time();
                    unset($params['ctime']);
                    $result = $this->model->allowField(true)->isUpdate(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '编辑成功');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $words = $this->model->where('id', $ids)->find();
        $words->tags = explode(',', $words->name);
        switch ($words->type) {
            case 'text':
                $words->text = $words->content;
                break;
            case 'image':
                $words->image = $words->content;
                break;
            case 'voice':
                $words->audio = $words->content;
                break;
            case 'video':
                $words->video = $words->content;
                break;
            case 'news':
                $words->news = $words->content;
                break;
        }
        $this->view->assign('words', $words);
        return $this->view->fetch('wechat/editwords');
    }

    /**
     * 删除关键字
     * @return [type] [description]
     */
    public function delwords($ids = '')
    {
        if (request()->isAjax()) {
            $i = (new WechatKey())->destroy($ids);
            if ($i) {
                return callback(200, '删除成功');
            } else {
                return callback(400, '删除失败');
            }
        }
    }

    /**
     * 关注回复消息
     */
    public function subscribe()
    {
        $this->model = new WechatSub();
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $this->dataLimit = false;
            $this->modelValidate = false;
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    if ($params['type'] == 'text' && empty($params['content'])) {
                        return callback(404, '请输入消息内容');
                    }
                    if ($params['type'] !== 'text' && empty($params['content'])) {
                        return callback(404, '请上传素材内容');
                    }
                    $subscribe = $this->model->get(1);
                    if (empty($subscribe)) {
                        $result = $this->model->allowField(true)->save($params);
                    } else {
                        unset($params['ctime']);
                        $result = $subscribe->allowField(true)->save($params);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '保存成功');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $words = $this->model->get(1);
        if (!empty($words)) {
            switch ($words->type) {
                case 'text':
                    $words->text = $words->content;
                    break;
                case 'image':
                    $words->image = $words->content;
                    break;
                case 'voice':
                    $words->audio = $words->content;
                    break;
                case 'video':
                    $words->video = $words->content;
                    break;
                case 'news':
                    $words->news = $words->content;
                    break;
            }
        } else {
            $words = [
                'type' => 'text',
                'status' => 1,
                'text' => '',
                'image' => '',
                'audio' => '',
                'video' => '',
                'news' => '',
                'content' => '',
            ];
            $words = json_encode($words);
        }
        $this->view->assign('words', $words);
        return $this->view->fetch('wechat/subscribe');
    }

    /**
     * 收到回复消息
     */
    public function received()
    {
        $this->model = new WechatRece();
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $this->dataLimit = false;
            $this->modelValidate = false;
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    if ($params['type'] == 'text' && empty($params['content'])) {
                        return callback(404, '请输入消息内容');
                    }
                    if ($params['type'] !== 'text' && empty($params['content'])) {
                        return callback(404, '请上传素材内容');
                    }
                    $received = $this->model->get(1);
                    if (empty($received)) {
                        $result = $this->model->allowField(true)->save($params);
                    } else {
                        unset($params['ctime']);
                        $result = $received->allowField(true)->save($params);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, '保存成功');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $words = $this->model->get(1);
        if (!empty($words)) {
            switch ($words->type) {
                case 'text':
                    $words->text = $words->content;
                    break;
                case 'image':
                    $words->image = $words->content;
                    break;
                case 'voice':
                    $words->audio = $words->content;
                    break;
                case 'video':
                    $words->video = $words->content;
                    break;
                case 'news':
                    $words->news = $words->content;
                    break;
            }
        } else {
            $words = [
                'type' => 'text',
                'status' => 1,
                'text' => '',
                'image' => '',
                'audio' => '',
                'video' => '',
                'news' => '',
                'content' => '',
            ];
            $words = json_encode($words);
        }
        $this->view->assign('words', $words);
        return $this->view->fetch('wechat/received');
    }
}