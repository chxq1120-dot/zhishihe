<?php

namespace app\common\model;

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu
{
    protected $auth;
    protected $bucket;
    protected $domain_url;

    function __construct()
    {
        $this->auth = new Auth(config('setting.upload_keyid_qiniu'), config('setting.upload_secret_qiniu'));
        $this->bucket = config('setting.upload_bucket_qiniu');
        $this->domain_url = config('setting.upload_domain_qiniu');
        if (stripos($this->domain_url, 'http') === false) {
            $this->domain_url = 'https://' . $this->domain_url;
        }
    }

    /**
     * 上传文件
     * @param  [type] $file      [description]
     * @param  [type] $extension [description]
     * @return [type]            [description]
     */
    public function uploadFile($file, $extension)
    {
        $token = $this->auth->uploadToken($this->bucket);
        $object = md5(uniqid(mt_rand(), true)) . '.' . $extension;
        $uploadMgr = new UploadManager();
        list($result,$err) = $uploadMgr->putFile($token, $object, $file);
        if ($err!==null) {
            return $this->ret(400, '上传失败: ' . $err['error']);
        }
        return $this->ret(200,'上传成功',['url'=>$this->domain_url . '/' . $result['key']]);
    }

    /**
     * [上传文件数据]
     * @param  [type] $content [description]
     * @return [type]          [description]
     */
    public function uploadData($content, $filename)
    {
        $token = $this->auth->uploadToken($this->bucket, $filename);
        $uploadMgr = new UploadManager();
        list($result,$err) = $uploadMgr->put($token, $filename, $content);
        if ($err!==null) {
            return $this->ret(400, '上传失败: ' . $err['error']);
        }
        return $this->ret(200,'上传成功',['url'=>$this->domain_url . '/' . $result['key']]);
    }

    /**
     * 文件流上传
     * @param $path
     * @param $stream
     * @return array
     */
    public function uploadStream($path,$resource)
    {
        $token = $this->auth->uploadToken($this->bucket, $path);
        $uploadMgr = new UploadManager();
        //使用闭包逐块读取流
        list($result,$err) = $uploadMgr->putFile($token, $path, $resource);
        if ($err!==null) {
            return $this->ret(400, '上传失败: ' . $err['error']);
        }
        return $this->ret(200, '上传成功', ['url' => $this->domain_url . '/' . $result['key']]);
    }

    /**
     * 下载文件保存到对象存储
     */
    public function download($url, $dir = 'uploads')
    {
        $filename = $this->setSaveName($url, $dir);
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $url, $m);
        $stream = file_get_contents($url);
        $token = $this->auth->uploadToken($this->bucket, $filename);
        $uploadMgr = new UploadManager();
        list($result,$err) = $uploadMgr->put($token, $filename, $stream);
        if ($err!==null) {
            return $this->ret(400, '上传失败: ' . $err['error']);
        }
        $data=[
            'url'=>$this->domain_url . '/' . $result['key'],
            'type'=>pathinfo($filename, PATHINFO_EXTENSION),
            'size'=>strlen($stream),
            'name'=>pathinfo($filename, PATHINFO_FILENAME) . '.' . pathinfo($filename, PATHINFO_EXTENSION),
            'title'=>$m ? $m[1] : ""
        ];
        return $this->ret(200,'下载成功',$data);
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
     * 删除文件
     * @param  [string] $object [文件]
     * @return [array]         [返回数组]
     */
    public function deleteObject($object)
    {
        $object = str_replace($this->domain_url . '/','', $object);
        $config = new \Qiniu\Config();
        $bucketMgr = new BucketManager($this->auth,$config);
        list($ret, $err) = $bucketMgr->delete($this->bucket, $object);
        if($err !== null){
            return $this->ret(400,$err['error']);
        }
        return $this->ret(200,'删除成功');
    }
    /**
     * 批量删除文件
     * @param  [array] $objects [文件数组]
     * @return [array]          [返回结果]
     */
    public function deleteObjects($objects)
    {
        $objects = str_replace($this->domain_url . '/','', $objects);
        $config = new \Qiniu\Config();
        $bucketMgr = new BucketManager($this->auth,$config);
        $ops = $bucketMgr->buildBatchDelete($this->bucket, $objects);
        list($ret, $err) = $bucketMgr->batch($ops);
        if ($err != null) {
            return $this->ret(400,$err['error']);
        } else {
            return $this->ret(200,'删除成功');
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