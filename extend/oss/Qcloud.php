<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2020/5/2
 * Time: 14:58
 */

namespace oss;

use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\CosException;
use think\Exception;
use think\facade\Config;
use think\facade\Request;

class Qcloud
{
    protected $qcloud;
    protected $bucket;
    protected $endint;
    protected $domain;
    protected $uploadInfo = [];

    public function __construct()
    {
        $key_id = config('setting.upload_keyid_qcloud');
        $secret = config('setting.upload_secret_qcloud');
        $this->endint = config('setting.upload_endpoint_qcloud');
        $this->bucket = config('setting.upload_bucket_qcloud');
        $this->domain = config('setting.upload_domain_qcloud');
        $this->qcloud = new Client(array(
            'region' => $this->endint,
            //'schema' => 'https',
            'credentials' => array(
                'secretId' => $key_id,
                'secretKey' => $secret
            )
        ));
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
     * 单文件上传文件
     */
    public function upload($input = 'file', $dir = 'uploads', $rule = ['size'=>1000 * 1024 * 1024,'ext' => ['gif','ico', 'jpg', 'jpeg', 'bmp', 'png']],$is_thumb=0,$is_water=0)
    {
        $file = request()->file($input);
        if (!$file) {
            return $this->ret(404, '上传文件不能为空');
        }
        //单图片上传
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
            'title' =>$file->getInfo('name'),
            'type' => pathinfo($file->getInfo('name'), PATHINFO_EXTENSION),
            'name' => pathinfo($path, PATHINFO_FILENAME).'.'.pathinfo($path, PATHINFO_EXTENSION),
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
        try {
            $result = $this->qcloud->putObject(['Bucket' =>$this->bucket,'Key' => $path,'Body' =>$resource]);
            if (getIsDomain($this->domain)) {
                $url = 'https://' . $this->domain . '/' . $result['Key'];
            } else {
                $url = 'https://' . $result['Location'];
            }
            return $this->ret(200, '上传成功', ['url' => $url]);
        } catch (CosException $e) {
            return $this->ret(400, $e->getMessage());
        }
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
        return $this->ret(200, 'success', ['url' => $result['data']['url']]);
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
     * 删除对象
     */
    public function deleteObject($object){
        try {
            if (getIsDomain($this->domain)) {
                $object = str_replace('https://' .$this->domain.'/', '', $object);
            } else {
                $object = str_replace('https://' . $this->bucket . '.cos.' . $this->endint.'.myqcloud.com/', '', $object);
            }
            $result = $this->qcloud->deleteObject(['Bucket' => $this->bucket, 'Key' =>$object]);
            if (!isset($result['Key'])) {
                return $this->ret(400,'删除失败');
            }
            return $this->ret(200,'删除成功');
        } catch (CosException $e) {
            return $this->ret(400,$e->getMessage());
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
        try {
            $result = $this->qcloud->upload($this->bucket, $path, $content);
            if (getIsDomain($this->domain)) {
                $url = 'https://'.$this->domain.'/'.$result['Key'];
            } else {
                $url = 'https://'.$result['Location'];
            }
            return $this->ret(200,'操作成功',['url' => $url]);
        } catch (CosException $e) {
            return $this->ret(400,$e->getMessage());
        }
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