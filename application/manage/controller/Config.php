<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\model\Versions;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use think\Db;
use app\common\model\Config as configModel;
use think\Exception;
use think\facade\Env;

class Config extends Common
{
    protected $model, $types_option, $groups;

    function initialize()
    {
        parent::initialize();
        $this->model = new configModel();
        $this->types_option = [
            'text' => '输入框',
            'textarea' => '多行文本',
            'checkbox' => '复选框',
            'radio' => '单选框',
            'switchs' => '开关',
            'select' => '下拉框',
            'image' => '图片上传',
            'number' => '数字输入框',
            'datetime' => '日期选择器',
            'ueditor' => '百度编辑器',
            'color' => '颜色选择器',
            'array' => '数组参数',
        ];
        $this->assign('types', $this->types_option);
        $this->groups = [];
        $group = $this->model->where('name', 'group')->value('default');
        if (!empty($group)) {
            $options = explode("\n", $group);
            foreach ($options as $option) {
                if (stripos($option, '|') !== false) {
                    $option = explode('|', $option);
                    $this->groups[trim($option[1])] = trim($option[0]);
                }
            }
        }
        $this->assign('groups', $this->groups);
    }

    /**
     * 系统参数列表
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order('indexid asc,id desc')
                ->count();

            $list = $this->model
                ->where($where)
                ->order('indexid asc,id desc')
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 发布上传微信小程序包
     */
    public function issue()
    {
        if ($this->request->isAjax()) {
            try{
                $type = $this->request->param("type/s", 'upload');
                $params = $this->request->post("row/a");
                if (empty($params['version'])) {
                    return callback(404, '请输入版本号');
                }
                if (empty($params['remark'])) {
                    return callback(404, '请输入版本说明');
                }
                #项目名称
                $appName = config('setting.web_name');
                #小程序接口地址
                $appUrl = $this->request->domain() . '/';
                #小程序appid
                $appId = config('setting.app_id');
                #上传私钥保存
                $privateKey = trim(config('setting.private_key'));
                if($privateKey=='--' || empty($privateKey)){
                    return callback(404, '请先填写小程序上传代码密钥');
                }
                #代码包路径
                $packPath = Env::get('root_path') . 'frontend' . DIRECTORY_SEPARATOR . 'mp-weixin';
                #处理代码包中的插件以及接口地址等
                #读取配置文件内容
                $wccinfo = "const wcceinfo = {'name':'" . $appName . "','version': '" . $params['version'] . "','siteurl': '" . $appUrl . "'};module.exports = wcceinfo;";
                $result = file_put_contents($packPath . DIRECTORY_SEPARATOR . 'wcceinfo.js', $wccinfo);
                if (!$result) {
                    return callback(404, '提交失败');
                }
                $appJson = file_get_contents($packPath . DIRECTORY_SEPARATOR . 'app.json');
                $appJson = json_decode($appJson, true);
                #处理appjson文件
                if (isset($appJson['usingComponents'])) {
                    unset($appJson['usingComponents']);
                }
                if (empty($params['plugins'])) {
                    unset($appJson['plugins']);
                } else {
                    if (empty($appJson['plugins'])) {
                        $appJson['plugins'] = [
                            'netdiskShare' => [
                                "version" => "1.2.6",
                                "provider" => "wx8c873f830774d652"
                            ]
                        ];
                    }
                }
                //JSON_UNESCAPED_SLASHES JSON_PRETTY_PRINT JSON_UNESCAPED_UNICODE ---/JSON_FORCE_OBJECT
                $appJson = json_encode($appJson, 448);
                $result = file_put_contents($packPath . DIRECTORY_SEPARATOR . 'app.json', $appJson);
                if (!$result) {
                    return callback(404, '提交失败');
                }
                #压缩代码包
                $zipName = $appId . '.zip';
                list($result, $msg) = $this->zipFolder(str_replace('mp-weixin', '', $packPath) . $zipName, $packPath);
                if (!$result) {
                    return callback(404, $msg);
                }
                $multipart = [
                    [
                        'name' => 'type',
                        'contents' => $type,
                    ],
                    [
                        'name' => 'appid',
                        'contents' => $appId,
                    ],
                    [
                        'name' => 'privateKey',
                        'contents' => $privateKey,
                    ],
                    [
                        'name' => 'version',
                        'contents' => $params['version'],
                    ],
                    [
                        'name' => 'desc',
                        'contents' => $params['remark'],
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen(str_replace('mp-weixin', '', $packPath) . $zipName, 'r'),
                    ],
                ];
                $api_url = 'https://cloud.zhipall.cn/api/upload';
                $client = new Client();
                $response = $client->post($api_url, ['multipart' => $multipart]);
                $code = $response->getStatusCode();
                if ($code !== 200) {
                    return json(['code' => 400, 'msg' => '操作失败，请稍后再试']);
                }
                $info = $response->getBody()->getContents();
                $info = json_decode($info, true);
                if ($info['code'] == 200) {
                    if (!empty($info['data']['base64'])) {
                        return json(['code' => 200, 'msg' => 'success', 'data' => ['preview' => $info['data']['base64']]]);
                    } else {
                        return json(['code' => 200, 'msg' => 'success']);
                    }
                } else {
                    return json(['code' => 402, 'msg' => $info['msg']]);
                }
            }catch (GuzzleException $e){
                return json(['code' => 402, 'msg' => $e->getMessage()]);
            }catch (Exception $e){
                return json(['code' => 402, 'msg' => $e->getMessage()]);
            }
        }
        $vnums = Versions::order('versions_nums desc')->value('vnums');
        $this->assign('vnums', $vnums);
        return $this->view->fetch();
    }

    /**
     * 压缩文件夹
     * @param $zip_path string 压缩包存储路径
     * @param $folder_path string 要要压缩的文件夹路径
     */
    protected function zipFolder($zip_path, $folder_path)
    {
        $rootPath = realpath($folder_path);
        $zip = new \ZipArchive();
        $result = $zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if (!$result) {
            return [false, '压缩代码包失败'];
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return [true, 'success'];
    }
}