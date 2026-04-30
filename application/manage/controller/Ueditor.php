<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace app\manage\controller;

use app\common\lib\Storage;
use oss\Alioss;
use oss\Qcloud;
use think\facade\Env;
use think\Image;
use app\common\model\Qiniu;

class Ueditor extends Common
{
    protected $setting;
    protected $dataLimit='personal';

    public function initialize()
    {
        parent::initialize();
        $this->setting = new \app\common\model\Setting();
        $this->model = new \app\common\model\Attachment();
    }
    /**
     * 编辑上传接口
     */
    public function index()
    {
        header("Content-Type: text/html; charset=utf-8");
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("./static/ueditor/php/config.json")), true);
        $action = $_GET['action'];
        switch ($action) {
            case 'config':
                $CONFIG['imageMaxSize'] = $CONFIG['imageMaxSize'] * 100;
                $CONFIG['scrawlMaxSize'] = $CONFIG['scrawlMaxSize'] * 100;
                $CONFIG['catcherMaxSize'] = $CONFIG['catcherMaxSize'] * 100;
                $CONFIG['videoMaxSize'] = $CONFIG['videoMaxSize'] * 100;
                $CONFIG['fileMaxSize'] = $CONFIG['fileMaxSize'] * 100;
                $result = json_encode($CONFIG, true);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $fieldName = $CONFIG['imageFieldName'];
                $result = $this->upImage($fieldName);
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $CONFIG['scrawlPathFormat'],
                    "maxSize" => $CONFIG['scrawlMaxSize'],
                    "allowFiles" => $CONFIG['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $CONFIG['scrawlFieldName'];
                $base64 = "base64";
                $result = $this->upBase64($config, $fieldName);
                break;
            /* 上传视频 */
            case 'uploadvideo':
                $fieldName = $CONFIG['videoFieldName'];
                $result = $this->upFile($fieldName);
                break;
            /* 上传文件 */
            case 'uploadfile':
                $fieldName = $CONFIG['fileFieldName'];
                $result = $this->upFile($fieldName);
                break;
            /* 列出图片 */
            case 'listimage':
                $allowFiles = $CONFIG['imageManagerAllowFiles'];
                $listSize = $CONFIG['imageManagerListSize'];
                $path = $CONFIG['imageManagerListPath'];
                $get = $_GET;
                $result = $this->fileList($allowFiles, $listSize, $get);
                break;
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $CONFIG['fileManagerAllowFiles'];
                $listSize = $CONFIG['fileManagerListSize'];
                $path = $CONFIG['fileManagerListPath'];
                $get = $_GET;
                $result = $this->fileList($allowFiles, $listSize, $get);
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $fieldName = $CONFIG['catcherFieldName'];
                /* 抓取远程图片 */
                $list = array();
                isset($_POST[$fieldName]) ? $source = $_POST[$fieldName] : $source = $_GET[$fieldName];
                #过滤本地域名、存储域名
                $domains=[
                    str_replace(['https://','http://'],['',''],$this->request->domain()),
                    str_replace(['https://','http://'],['',''],config('setting.upload_domain')),
                    str_replace(['https://','http://'],['',''],config('setting.upload_domain_qcloud')),
                    str_replace(['https://','http://'],['',''],config('setting.upload_domain_qiniu')),
                    'aliyuncs.com',
                    'myqcloud.com',
                ];
                $storage=new Storage();
                foreach ($source as $imgUrl) {
                    $img_domain = parse_url($imgUrl, PHP_URL_HOST);
                    if(in_array($img_domain, $domains)){
                        continue;
                    }
                    if(stripos($img_domain,$domains[4])!==false){
                        continue;
                    }
                    if(stripos($img_domain,$domains[5])!==false){
                        continue;
                    }
                    list($result,$info)=$storage->download(5,$this->admin_uid,$imgUrl,0,1);
                    if(!$result){
                        continue;
                    }
                    array_push($list, [
                        "state" => 'SUCCESS',
                        "url" => $info['url'],
                        "size" =>$info["size"],
                        "title" => $info['name'],
                        "original" =>$info['title'],
                        "source" => htmlspecialchars($imgUrl)
                    ]);
                }
                $result = json_encode([
                    'state' => count($list) ? 'SUCCESS' : 'ERROR',
                    'list' => $list
                ]);
                break;
            default:
                $result = json_encode(['state' => '请求地址出错']);
                break;
        }
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(['state' => 'callback参数不合法']);
            }
        } else {
            echo $result;
        }
    }

    //上传图片
    private function upImage()
    {
        $storage=new Storage();
        list($result,$info)=$storage->upload(0,$this->admin_uid,0,1);
        if(!$result){
            $data =[
                'state' => $info
            ];
        }else{
            $data = $info;
            $data['state']='SUCCESS';
            $data['original']=$data['title'];
            $data['title']=$data['name'];
        }
        return json_encode($data);
    }

    //上传文件
    private function upFile(){
        $storage=new Storage();
        list($result,$info)=$storage->upload(0,$this->admin_uid,0,0);
        if(!$result){
            $data =[
                'state' => $info
            ];
        }else{
            $data = $info;
            $data['state']='SUCCESS';
            $data['original']=$data['title'];
            $data['title']=$data['name'];
        }
        return json_encode($data);
    }

    //列出图片
    protected function fileList($allowFiles, $listSize, $get)
    {
        $allowFiles=str_replace('.','',$allowFiles);
        /* 获取参数 */
        $size = isset($get['size']) ? htmlspecialchars($get['size']) : $listSize;
        $start = isset($get['start']) ? htmlspecialchars($get['start']) : 0;
        $where = [];
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            array_push($adminIds,0);
            $where[] = ['admin_id', 'in', $adminIds];
        }
        if(!empty($allowFiles)){
            $allowFiles=implode(',',$allowFiles);
            $maps="FIND_IN_SET(att_type,'".$allowFiles."')";
        }
        $total=$this->model->where($where)->where($maps)->count();
        $list=$this->model->where($where)->where($maps)->limit($start,$size)->order('id desc')->select();
        if (empty($list)) {
            return json_encode([
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => 0
            ]);
        }
        $filelist=[];
        foreach ($list as $k => $v) {
            $filelist[] = [
                'url' => $v['up_type']==1?$this->request->domain() .$v['satt_dir']:$v['satt_dir'],
                'title'=>$v['real_name'],
                'alt'=>$v['real_name'],
                'mtime' => $v['ctime']
            ];
        }
        /* 返回数据 */
        $result = json_encode([
            "state" => "SUCCESS",
            "list" => $filelist,
            "start" => $start,
            "total" => $total
        ]);
        return $result;
    }
    /*
	 * 处理base64编码的图片上传
	 * 例如：涂鸦图片上传
	*/
    private function upBase64($config, $fieldName)
    {
        $base64Data = $_POST[$fieldName];
        $img = base64_decode($base64Data);

        $dirname = './uploads/scrawl/';
        $file['filesize'] = strlen($img);
        $file['oriName'] = $config['oriName'];
        $file['ext'] = strtolower(strrchr($config['oriName'], '.'));
        $file['name'] = uniqid() . $file['ext'];
        $file['fullName'] = $dirname . $file['name'];
        $fullName = $file['fullName'];

        //检查文件大小是否超出限制
        if ($file['filesize'] >= ($config["maxSize"])) {
            return json_encode(['state' => '文件大小超出网站限制']);
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            return json_encode(['state' => '目录创建失败']);
        } else if (!is_writeable($dirname)) {
            return json_encode(['state' => '目录没有写权限']);
        }

        //移动文件
        if (!(file_put_contents($fullName, $img) && file_exists($fullName))) { //移动失败
            $data = [
                'state' => '写入文件内容错误',
            ];
        } else { //移动成功
            $data = [
                'state' => 'SUCCESS',
                'url' => str_replace('./uploads', $this->request->domain() . '/uploads', $file['fullName']),
                'title' => $file['name'],
                'original' => $file['oriName'],
                'type' => $file['ext'],
                'size' => $file['filesize'],
            ];
        }
        return json_encode($data);
    }
}