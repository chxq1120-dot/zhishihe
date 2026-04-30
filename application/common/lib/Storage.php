<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/11/13
 * Time: 18:24
 */

namespace app\common\lib;

use app\common\model\Attachment;
use app\common\model\Qiniu;
use oss\Alioss;
use oss\Qcloud;
use think\Controller;
use think\Exception;
use think\facade\Env;
use think\Image;

class Storage extends Controller
{
    protected $app_mchid;
    protected $app_key;
    protected $api_url;
    protected $upload_ext;
    protected $upload_size;
    protected $upload_thumb_open;
    protected $upload_water_open;
    public function __construct()
    {
        parent::__construct();
        $this->upload_size=intval(config('setting.upload_size'))*1024*1024;
        $this->upload_ext=config('setting.upload_ext');
        $this->upload_thumb_open=intval(config('setting.upload_thumb_open'));
        $this->upload_water_open=intval(config('setting.upload_water_open'));
    }
    /**
     * 统一文件上传接口
     * @param int $pid
     * @param int $admin_id
     * @param int $is_thumb
     * @param int $is_water
     * @return array
     */
    public function upload($pid=0,$admin_id=1,$is_thumb=0,$is_water=0)
    {
        try {
            $fileKey = array_keys(request()->file());
            $file = request()->file($fileKey[0]);
            #验证文件类型和大小
            if (!$file->check(['size' => $this->upload_size, 'ext' => $this->upload_ext])) {
                throw new Exception($file->getError());
            }
            #获取上传文件属性
            $storage_type = strtolower(config('setting.upload_storage'));
            $originName = $file->getInfo('name');
            $tmpName = $file->getInfo('tmp_name');
            $extension = pathinfo($originName, PATHINFO_EXTENSION);
            $filename = md5(microtime(true)) . '.' . $extension;
            $saveName = date('Ymd') . DIRECTORY_SEPARATOR . $filename;
            $savePath = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads'. DIRECTORY_SEPARATOR . $saveName;
            $fileSize = filesize($tmpName);
            #组装数据
            $data = [
                'name' => $filename,
                'title' => $originName,
                'path' => '/uploads/' . $saveName,
                'url' => $this->request->domain().'/uploads/' . $saveName,
                'size' => $fileSize,
                'type' => $extension,
                'up_type'=>1
            ];
            //本地上传或图片文件
            if($storage_type=='local' || $file->checkImg()) {
                //创建目录
                $dir = dirname($savePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                //流式写入文件
                $source = fopen($tmpName, 'rb');
                $dest = fopen($savePath, 'wb');
                if (!$source || !$dest) {
                    throw new Exception('无法打开文件进行流式处理');
                }
                stream_copy_to_stream($source, $dest);
                fclose($source);
                fclose($dest);
            }
            //图片文件处理
            if($file->checkImg()){
                #图片缩略图
                if ($is_thumb == 1 && $this->upload_thumb_open == 1) {
                    $this->thumb($savePath);
                }
                #图片水印
                if ($is_water == 1 && $this->upload_water_open == 1) {
                    $this->water($savePath);
                }
            }
            switch($storage_type) {
                case 'aliyun':#阿里云
                    if($file->checkImg()) {
                        $handle = fopen($savePath, 'rb');
                    }else{
                        $handle = fopen($tmpName, 'rb');
                    }
                    if (!$handle) {
                        throw new Exception('无法打开上传文件');
                    }
                    $path = str_replace('\\', '/', 'uploads' . DIRECTORY_SEPARATOR . $saveName);
                    $oss = new Alioss();
                    $info = $oss->uploadStream($path, $handle);
                    if ($info['status'] !== 200) {
                        throw new Exception($info['msg']);
                    }
                    if(is_resource($handle)) fclose($handle);
                    #移除本地图片
                    if(file_exists($savePath)) @unlink($savePath);
                    $data['url'] = $info['data']['url'];
                    $data['up_type'] = 2;
                    break;
                case 'qcloud':#腾讯云
                    if($file->checkImg()) {
                        $handle = fopen($savePath, 'rb');
                    }else{
                        $handle = fopen($tmpName, 'rb');
                    }
                    if (!$handle) {
                        throw new Exception('无法打开上传文件');
                    }
                    $path = str_replace('\\', '/', 'uploads' . DIRECTORY_SEPARATOR . $saveName);
                    $oss = new Qcloud();
                    $info = $oss->uploadStream($path, $handle);
                    if ($info['status'] == !200) {
                        throw new Exception($info['msg']);
                    }
                    if(is_resource($handle)) fclose($handle);
                    #移除本地图片
                    if (file_exists($savePath)) @unlink($savePath);
                    $data['url'] = $info['data']['url'];
                    $data['up_type'] = 3;
                    break;
                case 'qiniu':#七牛云
                    if($file->checkImg()) {
                        $handle = $savePath;
                    }else{
                        $handle = $tmpName;
                    }
                    $path = str_replace('\\', '/', 'uploads' . DIRECTORY_SEPARATOR . $saveName);
                    $oss = new Qiniu();
                    $info = $oss->uploadStream($path, $handle);
                    if ($info['status'] !== 200) {
                        throw new Exception($info['msg']);
                    }
                    #移除本地图片
                    if (file_exists($savePath)) @unlink($savePath);
                    $data['url'] = $info['data']['url'];
                    $data['up_type'] = 4;
                    break;
            }
            list($result, $msg) = $this->createAttachment($pid, $data, $admin_id);
            if (!$result) {
                throw new Exception($msg);
            }
            return [true, $data];
        }catch (Exception $e){
            return [false,$e->getMessage()];
        }
    }

    /**
     * 统一文件下载接口
     * * @param int $pid
     * * @param int $admin_id
     * * @param string $url 文件地址
     * * @param int $is_thumb
     * * @param int $is_water
     * @return array
     */
    public function download($pid=0,$admin_id=1,$url=null,$is_thumb=0,$is_water=0)
    {
        try {
            if(empty($url)){
                throw new Exception('文件地址不能为空');
            }
            #存储方式
            $storage_type = strtolower(config('setting.upload_storage'));
            switch ($storage_type){
                case 'local':
                    $result=$this->saveRemote($url,$this->upload_ext);
                    if ($result['status']!==200) {
                        throw new Exception($result['msg']);
                    }
                    $result['data']['up_type']=1;
                    break;
                case 'aliyun':
                    $oss = new Alioss();
                    $result = $oss->download($url);
                    if ($result['status'] !== 200) {
                        throw new Exception($result['msg']);
                    }
                    $result['data']['up_type']=2;
                    break;
                case 'qcloud':
                    $oss = new Qcloud();
                    $result = $oss->download($url);
                    if ($result['status'] !== 200) {
                        throw new Exception($result['msg']);
                    }
                    $result['data']['up_type']=3;
                    break;
                case 'qiniu':
                    $qiniu = new Qiniu;
                    $result = $qiniu->download($url);
                    if ($result['status'] !== 200) {
                        throw new Exception($result['msg']);
                    }
                    $result['data']['up_type']=4;
                    break;
            }
            list($result, $msg) = $this->createAttachment($pid, $result['data'], $admin_id);
            if (!$result) {
                throw new Exception($msg);
            }
            return [true,$result['data']];
        }catch (Exception $e){
            return [false,$e->getMessage()];
        }
    }
    /**
     * 写入附件记录
     * @param $pid int 分类ID
     * @param $files array 文件信息
     * @param $admin_id int 所属代理ID
     * @return array
     */
    protected function createAttachment($pid, $files, $admin_id)
    {
        try {
            $data = [
                'admin_id'=>$admin_id,
                'pid' => $pid,
                'name' => $files['name'],
                'real_name' => $files['title'],
                'att_dir' => $files['up_type']==1?$files['path']:$files['url'],
                'satt_dir' => $files['url'],
                'att_size' => $files['size'],
                'att_type' => $files['type'],
                'up_type' => $files['up_type'],
                'mode_type' => 1,
                'ctime' => time(),
            ];
            $result = Attachment::create($data, true);
            if (!$result) {
                return [false, '上传失败'];
            }
            return [true, '上传成功'];
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }
    //抓取远程图片本地保存
    private function saveRemote($fieldName,$allowFiles="png,jpg,jpeg,gif,bmp")
    {
        $imgUrl = htmlspecialchars($fieldName);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            return ['status' =>400,'msg'=>'链接不是http链接'];
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            return ['status' =>400,'msg'=>'链接不可用'];
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr(parse_url($imgUrl, PHP_URL_PATH), '.'));
        $fileExt=str_replace( '.', '',$fileType);
        if (strpos($allowFiles,$fileExt)===false) {
            return ['status' =>400,'msg'=>'文件不允许上传'];
        }
        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $dirname = './uploads/remote/';
        $file['oriName'] = $m ? $m[1] : "";
        $file['filesize'] = strlen($img);
        $file['name'] = uniqid() . $fileType;
        $file['fullName'] = $dirname . $file['name'];
        $file['ext'] = $fileExt;
        $fullName = $file['fullName'];

        //检查文件大小是否超出限制
        if ($file['filesize'] >= $this->upload_size) {
            return ['status' =>400,'msg'=>'文件大小超出系统限制'];
        }
        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            return ['status' =>400,'msg'=>'目录创建失败'];
        } else if (!is_writeable($dirname)) {
            return ['status' =>400,'msg'=>'目录没有写权限'];
        }
        //移动文件
        if (!(file_put_contents($fullName, $img) && file_exists($fullName))) { //移动失败
            return ['status' =>400,'msg'=>'写入文件内容错误'];
        } else { //移动成功
            return ['status' =>200,'msg'=>'写入文件内容错误','data'=>[
                'path' => str_replace('./uploads', '/uploads', $file['fullName']),
                'url' => $this->request->domain().str_replace('./uploads', '/uploads', $file['fullName']),
                'name' => $file['name'],
                'title' => $file['oriName'],
                'type' => $file['ext'],
                'size' => $file['filesize'],
            ]];
        }
    }
    /**
     *图片水印
     * @param $path string 图片路径
     */
    protected function water($path)
    {
        $image = Image::open($path);
        $type = config('setting.upload_water_types');#水印类型
        $wather = config('setting.upload_water_image');//水印图片
        $alpha = config('setting.upload_water_alpha');#透明度
        $angle = config('setting.upload_water_angle');#倾斜度
        $text = config('setting.upload_water_text');#水印文字
        $font = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR .'static'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'msyh.ttc';
        $size = config('setting.upload_water_size');#文字大小
        $color = config('setting.upload_water_color');#文字颜色
        $color=$this->covertRgba($color,$alpha);
        $pos = config('setting.upload_water_locate');#水印位置
        $x = config('setting.upload_water_posx');#X位置
        $y = config('setting.upload_water_posy');#Y位置
        $offset = [-$x, -$y];
        switch ($type) {
            case 1:#图片水印
                $file_path='.'.parse_url($wather,  PHP_URL_PATH);
                if(!file_exists($file_path)){
                    $result=$this->saveRemote($wather);
                    if($result['status']==200){
                        $wather='.'.$result['data']['url'];
                    }
                }else{
                    $wather=$file_path;
                }
                $image->water($wather, $pos, $alpha)->save($path);
                break;
            case 2:#文字水印
                $image->text($text, $font, $size, $color, $pos, $offset,$angle)->save($path);
                break;
        }
    }

    /**
     * 转换颜色模式
     * @param $color
     * @param $alpha
     * @return array
     */
    protected function covertRgba($color,$alpha)
    {
        $color = str_split(substr($color, 1),2);
        $color = array_map('hexdec', $color);
        if (empty($color[3]) || $color[3] > 127) {
            $color[3] = $alpha;
        }
        return $color;
    }
    /**
     *缩略图
     * @param $path string 图片路径
     * @param $width int 缩略图宽度
     * @param $height int 缩略图高度
     */
    protected function thumb($path,$width=0, $height=0)
    {
        $wid = config('setting.upload_thumb_width');
        $hei = config('setting.upload_thumb_height');
        $position = intval(config('setting.upload_thumb_types'));
        if (!empty($width)) {
            $wid = $width;
        }
        if (!empty($height)) {
            $hei = $height;
        }
        $image = Image::open($path);
        $image->thumb($wid, $hei, $position)->save($path);
    }
}