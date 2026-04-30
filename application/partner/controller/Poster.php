<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\partner\controller;

use app\common\model\Poster as posterModel;
use app\common\model\PosterBoard;
use EasyWeChat\Kernel\Support\File;
use oss\Alioss;
use PosterMaker\PosterMaker;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\facade\Env;

class Poster extends Common
{

    protected $searchFields = 'name';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;

    protected $sceneTag = 'article';

    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new posterModel();
    }

    /**
     * 列表
     */
    public function index()
    {
        //设置过滤方法
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $list = $this->model
            ->withJoin(['board' => ['name'], 'admin' => ['username']], 'left')
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
        $this->view->assign("list", $list);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->admin_uid;
                }
                $result = false;
                $this->modelValidate = false;

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
                    $board_param = PosterBoard::where('id', $params['board_id'])->find();
                    if (empty($board_param)) {
                        return callback(404, '请选择海报模板');
                    }
                    $boards = json_decode($board_param->params, true);
                    foreach ($boards as $k=>$v) {
                        if ($v['type'] == 'text' && $v['show'] == 1) {
                            $boards[$k]['txt'] = $params[$v['name']];
                        } elseif ($v['type'] == 'image') {
                            $qrcodeUrl=(new Spread())->qrcodeurl();
                            if($qrcodeUrl['status']==200){
                                $boards[$k]['url'] = $qrcodeUrl['data']['imgurl'];
                            }else{
                                return callback(404, '海报模板生成失败');
                            }
                        }
                    }
                    #组装海报
                    $width = 621;
                    $height = 1104;
                    $path = 'uploads/agent/poster';
                    $filename = 'poster_' . $params['board_id'] . '_' . time() . '.png';
                    $poster = new PosterMaker($width, $height, [255, 255, 255]);
                    $poster->addImg($board_param->url, [0, 0], [$width, $height], 0);
                    foreach ($boards as $board) {
                        if ($board['type'] == 'text') {
                            $font_path = Env::get('root_path') . '/public/static/admin/fonts/simhei.ttf';
                            if ($board['name'] == 'title') {
                                $font_path = Env::get('root_path') . '/public/static/admin/fonts/ysbt.ttf';
                            } elseif ($board['name'] == 'sub1') {
                                $font_path = Env::get('root_path') . '/public/static/admin/fonts/pmzdbt.ttf';
                            }
                            $poster->addText($board['txt'], $board['size'], [$board['x'], $board['y']], $board['color'], $font_path, 0, $board['max']);
                        } elseif ($board['type'] == 'image') {
                            $poster->addImg($board['url'], [$board['x'], $board['y']], [$board['width'], $board['height']], 0);
                        }
                    }
                    $content = $poster->render($filename, 1); // 保持为图片
                    $storage_type = config('setting.upload_storage');
                    if ($storage_type == 'local') {
                        list($res, $info) = $this->savePoster($content, $path, $filename, true);
                        if (!$res) {
                            return callback(404, $info);
                        }
                        $params['url'] = $this->request->domain() . '/' . $path . '/' . $info;
                    } else {
                        $oss = new Alioss();
                        $result = $oss->pudata($filename, $content, $path);
                        $params['url'] = str_replace('http://', 'https://', $result['data']['url']);
                    }
                    $params['admin_id'] = $this->admin_uid;
                    $params['ctime'] = time();
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }

        $board = \app\common\model\PosterBoard::order('indexid asc')->select();
        $this->view->assign("board", $board);
        return $this->view->fetch();
    }

    /**
     * 保存本地图片
     */
    protected function savePoster($contents, $directory, $filename = '', $appendSuffix = true)
    {
        $directory = rtrim($directory, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // @codeCoverageIgnore
        }
        if (!is_writable($directory)) {
            return [false, sprintf("'%s' 目录写入失败.", $directory)];
        }
        if (empty($contents) || '{' === $contents[0]) {
            return [false, 'Invalid media response content.'];
        }
        if ($appendSuffix && empty(pathinfo($filename, PATHINFO_EXTENSION))) {

            $filename .= File::getStreamExt($contents);

        }
        file_put_contents($directory . '/' . $filename, $contents);
        return [true, $filename];
    }

    /**
     * 获取海报配置参数
     */
    public function getParams($ids = 0)
    {
        if ($this->request->isAjax()) {
            $board = PosterBoard::where('id', $ids)->find();
            if (empty($board)) {
                return callback(404, '配置获取失败');
            }
            return callback(200, 'success', '', ['params' => $board->params]);
        }
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                return callback(404, '您没有权限');
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                $this->modelValidate = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
                    $board_param = PosterBoard::where('id', $params['board_id'])->find();
                    if (empty($board_param)) {
                        return callback(404, '请选择海报模板');
                    }
                    $boards = json_decode($board_param->params, true);
                    foreach ($boards as $k=>$v) {
                        if ($v['type'] == 'text' && $v['show'] == 1) {
                            $boards[$k]['txt'] = $params[$v['name']];
                        } elseif ($v['type'] == 'image') {
                            $qrcodeUrl=(new Spread())->qrcodeurl();
                            if($qrcodeUrl['status']==200){
                                $boards[$k]['url'] = $qrcodeUrl['data']['imgurl'];
                            }else{
                                return callback(404, '海报模板生成失败');
                            }
                        }
                    }
                    #组装海报
                    $width = 621;
                    $height = 1104;
                    $path = 'uploads/agent/poster';
                    $filename = 'poster_' . $params['board_id'] . '_' . time() . '.png';
                    $poster = new PosterMaker($width, $height, [255, 255, 255]);
                    $poster->addImg($board_param->url, [0, 0], [$width, $height], 0);
                    foreach ($boards as $board) {
                        if ($board['type'] == 'text') {
                            $font_path = Env::get('root_path') . '/public/static/admin/fonts/simhei.ttf';
                            if ($board['name'] == 'title') {
                                $font_path = Env::get('root_path') . '/public/static/admin/fonts/ysbt.ttf';
                            } elseif ($board['name'] == 'sub1') {
                                $font_path = Env::get('root_path') . '/public/static/admin/fonts/pmzdbt.ttf';
                            }
                            $poster->addText($board['txt'], $board['size'], [$board['x'], $board['y']], $board['color'], $font_path, 0, $board['max']);
                        } elseif ($board['type'] == 'image') {
                            $poster->addImg($board['url'], [$board['x'], $board['y']], [$board['width'], $board['height']], 0);
                        }
                    }
                    $content = $poster->render($filename, 1); // 保持为图片
                    $storage_type = config('setting.upload_storage');
                    if ($storage_type == 'local') {
                        list($res, $info) = $this->savePoster($content, $path, $filename, true);
                        if (!$res) {
                            return callback(404, $info);
                        }
                        $params['url'] = $this->request->domain() . '/' . $path . '/' . $info;
                    } else {
                        $oss = new Alioss();
                        $result = $oss->pudata($filename, $content, $path);
                        $params['url'] = str_replace('http://', 'https://', $result['data']['url']);
                    }
                    $params['ctime'] = time();
                    $result = $row->allowField(true)->save($params);
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
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }
        $board = \app\common\model\PosterBoard::order('indexid asc')->select();
        $this->view->assign("board", $board);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}