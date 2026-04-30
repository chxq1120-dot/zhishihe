<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2020/5/2
 * Time: 14:58
 */

namespace oss;

use OSS\Core\OssException;
use OSS\OssClient;
use think\Exception;
use think\facade\Config;
use think\facade\Request;

class Alioss
{
    protected $alioss;
    protected $bucket;
    protected $endint;
    protected $domain;
    protected $uploadInfo = [];

    public function __construct()
    {
        $key_id = trim(Config::get('setting.upload_keyid'));
        $secret = trim(Config::get('setting.upload_secret'));
        $this->endint = trim(Config::get('setting.upload_endpoint'));
        $this->domain = trim(Config::get('setting.upload_domain'));
        $this->bucket = trim(Config::get('setting.upload_bucket'));
        $this->alioss = new OssClient($key_id, $secret, $this->endint);
    }

    /**
     * 图片数据上传对象存储
     */
    public function pudata($name, $content, $dir = 'uploads')
    {
        $path = str_replace('\\', '/', $dir . DIRECTORY_SEPARATOR . $name);
        $result = $this->putObject($path, $content);
        if ($result['status'] !== 200) {
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success', ['url' => $result['data']['url']]);
    }
    /**
     * 上传文件
     */
    public function upload($input = 'file', $dir = 'uploads', $rule = ['size' => 1000 * 1024 * 1024,'ext' => ['gif', 'ico', 'jpg', 'jpeg', 'bmp', 'png']])
    {
        $file = request()->file($input);
        if (!$file) {
            return $this->ret(404, '上传文件不能为空');
        }
        //图片验证
        $check_rule = $file->check($rule);
        if (!$check_rule) {
            return $this->ret(404, $file->getError());
        }
        $path = $this->setSaveName($file, $dir);
        $content = file_get_contents($file->getInfo('tmp_name'));
        $result = $this->putObject($path, $content);
        if ($result['status'] !== 200) {
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success', [
            'url' => $result['data']['url'],
            'title' => $file->getInfo('name'),
            'type' => pathinfo($path, PATHINFO_EXTENSION),
            'name' => pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION),
            'size' => $file->getInfo('size')
        ]);
    }
    /**
     * description 流式上传
     * @param $path
     * @param $resource
     * @return array|bool|mixed
     */
    public function uploadStream($path, $resource)
    {
        $result = $this->baseCall('uploadStream', [$this->bucket, $path, $resource]);
        if ($result['status'] !== 200) {
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success', [
            'url' => $result['data']['url']
        ]);
    }

    /**
     * 删除文件
     * @param $object string 文件名
     * return array;
     */
    public function delObject($object)
    {
        if (getIsDomain($this->domain)) {
            $object = str_replace('https://' . $this->domain.'/', '', $object);
        } else {
            $object = str_replace('https://' . $this->bucket . '.' . $this->endint.'/', '', $object);
        }
        $result = $this->baseCall('deleteObject', [$this->bucket,$object]);
        if ($result['status'] !== 200) {
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success');
    }

    /**
     * 下载文件保存到对象存储
     */
    public function download($url, $dir = 'uploads')
    {
        $path = $this->setSaveName($url, $dir);
        $stream = file_get_contents($url);
        $result = $this->putObject($path, $stream);
        if ($result['status'] !== 200) {
            return $this->ret(404, $result['msg']);
        }
        $result['data']['type']=pathinfo($path, PATHINFO_EXTENSION);
        $result['data']['size']=strlen($stream);
        $result['data']['name']=pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        $result['data']['title']=pathinfo($url, PATHINFO_FILENAME) . '.' . pathinfo($url, PATHINFO_EXTENSION);
        return $this->ret(200, 'success', $result['data']);
    }

    /**
     * description 设置上传信息
     * @param string $result
     * @param string $msg
     * @param string $path
     */
    protected function setUploadInfo($result, $msg, $path)
    {
        array_push($this->uploadInfo, ['result' => $result, 'msg' => $msg, 'path' => $path]);
    }

    /**
     * description 设置文件名路径
     * @param $info mixed 上传文件对象
     * @param $dir
     * @return string
     */
    protected function setSaveName($info, $dir)
    {
        if (is_object($info)) {
            $path = $info->getInfo('name');
        } else {
            $path = $info;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'jpg';
        }
        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true)) . '.' . $ext;
        return str_replace('\\', '/', $dir . DIRECTORY_SEPARATOR . $savename);
    }

    /**
     * description 底层调用
     * @param $method
     * @param $arguments
     * @param bool $isRetOriginal
     * @return array|bool|mixed
     */
    public function baseCall($method, $arguments, $isRetOriginal = false)
    {
        if (!in_array($method, get_class_methods($this->alioss)))
            return $this->ret(500, '方法不存在');
        try {
            $result = call_user_func_array(array($this->alioss, $method), $arguments);
            if ($isRetOriginal) return $result;
            $url = $result['info']['url'];
            if (getIsDomain($this->domain)) {
                $url = str_replace($this->bucket . '.' . $this->endint, $this->domain, $url);
            }
            $url = str_replace('http://', 'https://', $url);
            return $this->ret(200, 'success', ['url' => $url]);
        } catch (Exception $e) {
            return $this->ret(500, $e->getMessage());
        }
    }

    /**
     * description 单或多图上传
     * @param $path
     * @param $content
     * @return array|bool|mixed
     */
    protected function putObject($path, $content)
    {
        return $this->baseCall('putObject', [$this->bucket, $path, $content]);
    }

    /**
     * description 定义返回格式
     * author chicho
     * @param int $code
     * @param string $msg
     * @param string $data
     * @return array
     */
    protected function ret($code = 200, $msg = '操作成功', $data = '')
    {
        return ['status' => $code, 'msg' => $msg, 'data' => $data];
    }
}