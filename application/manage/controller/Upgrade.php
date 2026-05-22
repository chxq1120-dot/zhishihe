<?php

namespace app\manage\controller;

use app\common\lib\UpService;
use think\Controller;
use app\common\model\Versions;
use GuzzleHttp\Client;
use think\Exception;
use think\facade\Env;

class Upgrade extends Common
{
    protected $current_version;
    protected $logFile;
    public function initialize(){
        parent::initialize();
        $this->model=new Versions();
        $this->current_version=$this->model->order('versions_nums desc')->find();
        $this->logFile = runtime_path() . 'upgrade_log.json';
    }
    protected function _readLog()
    {
        if (file_exists($this->logFile)) {
            $content = file_get_contents($this->logFile);
            $data = json_decode($content, true);
            return is_array($data) ? $data : [];
        }
        return [];
    }
    protected function _writeLog($data)
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->logFile, json_encode($data));
    }
    public function index()
    {
        if($this->request->isAjax()){
            $upgradeSer=new UpService();
            $result=$upgradeSer->newVersions($this->current_version['versions_nums']);
            if($result['status']!==1){
                return callback($result['status'], $result['msg']);
            }
            return callback(1, 'success','',$result['data']);
        }
        $this->current_version['content'] = str_replace("\n", '<br/>', $this->current_version['content']);
        $this->current_version['content'] = str_replace(['新增：','优化：','修复：'], ['<span class="blue_block">新增</span> ','<span class="green_block">优化</span> ','<span class="orange_block">修复</span> '], $this->current_version['content']);
        $this->assign('currentVer', $this->current_version);
        return $this->fetch();
    }
    public function upgradeLog()
    {
         $this->request->filter(['strip_tags']);
         if ($this->request->isAjax()) {
             if($this->request->request('keyField')){
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
             $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
             return json($result);
        }
    }
    public function showlog($ids = 0)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        $row->content= str_replace("\n", '<br/>', $row->content);
        $row->content= str_replace(['新增：','优化：','修复：'], ['<span class="blue_block">新增</span> ','<span class="green_block">优化</span> ','<span class="orange_block">修复</span> '], $row->content);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }   
    public function getUpList()
    {
        try{
            $logContent=['progress'=>1,'desc'=>'开始获取更新包文件...','type'=>1];
            $this->setUpgradeLog($logContent);
            $upgradeSer=new UpService();
            $result=$upgradeSer->getVerlist($this->current_version['versions_nums']);
            if($result['status']!==1){
                return callback($result['status'], $result['msg']);
            }
            $package_num=count($result['data']);
            $logContent=['progress'=>2,'desc'=>'已经获取到'.$package_num.'个更新包文件...','type'=>0];
            $this->setUpgradeLog($logContent);
            return callback(1,'success','', ['list'=>$result['data'],'nums'=>$package_num]);
        }catch (\Exception $e) {
            return callback(0, $e->getMessage());
        }
    }

    public function getUpgradeLog($type=0)
    {
        $logs = $this->_readLog();
        if(empty($logs)){
            return callback(0, '暂无日志数据');
        }
        $first_log = $logs[0];
        $data=[
            'progress'=>$first_log['progress'],
            'desc'=>$first_log['desc'],
        ];
        if($type==1){
            return $data;
        }
        return callback(1, 'success','',$data);
    }
    protected function setUpgradeLog($logContent)
    {
        try{
            if($logContent['type']==1){
                $logs = [];
            } else {
                $logs = $this->_readLog();
            }
            $logContent['time']=time();
            array_unshift($logs, $logContent);
            $this->_writeLog($logs);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function updateFile()
    {
        if($this->request->isPost()){
            try{
                if(session_status() === PHP_SESSION_ACTIVE) {
                    session_abort();
                }
                $data = input('param.');
                $progress=$this->getUpgradeLog(1);
                $now_progress=$progress['progress'];
                $average=bcdiv(60,$data['nums'],2);
                
                $zipFileName = 'v' . $data['version_nums'] . '.zip';
                $logContent=['progress'=>$now_progress,'desc'=>'开始下载更新包'.$zipFileName.'...','type'=>0];
                $this->setUpgradeLog($logContent);

                $client = new Client();
                $zipFilePath = env('root_path') . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'package';
                if (!is_dir($zipFilePath)) {
                    mkdir($zipFilePath, 0755, true);
                }
                $zipFileRes = fopen($zipFilePath . DIRECTORY_SEPARATOR . $zipFileName, 'w');
                $response = $client->request('GET', $data['file'], [
                    'sink' => $zipFileRes,
                    'progress'=>function($downTotal,$downedBytes) use ($average,$now_progress,$zipFileName){
                        if($downedBytes>0 && $downTotal>0){
                            $percent=bcmul(bcdiv($downedBytes,$downTotal,4),100,2);
                            if($percent>0){
                                $current_progress=bcadd($now_progress,bcmul($percent,bcdiv($average,100,2),2));
                                $logContent=['progress'=>$current_progress,'desc'=>'正在下载更新包'.$zipFileName.'...'.$percent.'%','type'=>0];
                                $this->setUpgradeLog($logContent);
                            }
                        }
                    }
                ]);
                $code = $response->getStatusCode();
                if ($code !== 200) {
                    return callback(0, '网络连接失败，请稍后再试');
                }
                $current_progress=bcadd($now_progress,$average,2);
                $logContent=['progress'=>$current_progress,'desc'=>'更新包' . $zipFileName . '下载完成','type'=>0];
                $this->setUpgradeLog($logContent);
                return callback(1, '更新包' . $zipFileName . '下载成功');
            }catch(\Exception $e){
                return callback(0,  $e->getMessage());
            }
        }
        return callback(0, '版本更新失败，请稍后再试');
    }
    public function copyFile()
    {
        if($this->request->isPost()){
            try{
                if(session_status() === PHP_SESSION_ACTIVE) {
                    session_abort();
                }
                $data = input('param.');
                $progress=$this->getUpgradeLog(1);
                $now_progress=$progress['progress'];
                $average=bcdiv(30,$data['nums'],2);
                $zipFileName = 'v' . $data['version_nums'] . '.zip';
                $logContent=['progress'=>$now_progress,'desc'=>'开始复制更新包'.$zipFileName.'文件...','type'=>0];
                $this->setUpgradeLog($logContent);
                $zipFilePath = env('root_path') . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'package';
                $toZipFilePath= env('root_path') . 'upgrade' . DIRECTORY_SEPARATOR . 'package';
                $zip = new \ZipArchive();
                if (!$zip->open($zipFilePath . DIRECTORY_SEPARATOR . $zipFileName)) {
                    return callback(0, '解压错误');
                }
                $zip->extractTo($toZipFilePath . DIRECTORY_SEPARATOR . 'v' . $data['version_nums']);
                $zip->close();
                $rootFrom = $toZipFilePath . DIRECTORY_SEPARATOR . 'v' . $data['version_nums'] . DIRECTORY_SEPARATOR . 'frame';
                $rootTo = env('root_path');
                $originalPath = $rootFrom . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'mp-weixin';
                $targetPath = $rootTo . 'frontend'.DIRECTORY_SEPARATOR .'mp-weixin'.DIRECTORY_SEPARATOR.'pages';
                if (is_dir($originalPath)) {
                    removeDir($targetPath);
                }
                copyFiles($rootFrom, $rootTo);
                $current_progress=bcadd($now_progress,$average,2);
                $logContent=['progress'=>$current_progress,'desc'=>'更新包'.$zipFileName.'文件复制完成','type'=>0];
                $this->setUpgradeLog($logContent);
                return callback(1, '更新包文件更新成功');
            }catch(\Exception $e){
                return callback(0,  $e->getMessage());
            }
        }
        return callback(0, '版本更新失败，请稍后再试');
    }
    public function update()
    {
        if($this->request->isPost()){
            try{
                $data = input('param.');
                $progress=$this->getUpgradeLog(1);
                $now_progress=$progress['progress'];
                $average=bcdiv(6,$data['nums'],2);
        
                $logContent=['progress'=>$now_progress,'desc'=>'开始更新'.$data['versions'].'版本数据...','type'=>0];
                $this->setUpgradeLog($logContent);
                $zipFilePath = env('root_path') . 'upgrade' . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR;
                $updateFile = $zipFilePath . 'v' . $data['version_nums'] . DIRECTORY_SEPARATOR . 'update'.DIRECTORY_SEPARATOR.'Update.php';
                $toFile = $zipFilePath . 'Update.php';
                if (!copy($updateFile, $toFile)) {
                    return callback(0, '数据文件更新失败');
                }
                require $toFile;
                $updateClass = controller('wcce\Update', 'wcce');
                $result = $updateClass->index();
                if ($result['code'] !== 200) {
                    return callback(0, $result['msg']);
                }
                $current_progress=bcadd($now_progress,$average,2);
                $logContent=['progress'=>$current_progress,'desc'=>$data['versions'].'版本数据更新完成','type'=>0];
                $this->setUpgradeLog($logContent);
                return callback(1, '更新包数据更新成功');
            }catch(\Exception $e){
                return callback(0,  $e->getMessage());
            }
        }
        return callback(0, '版本更新失败，请稍后再试');
    }
    public function upVersions()
    {
        if($this->request->isPost()){
            try{
                $data = input('param.');
                $versions=Versions::where('versions_nums',$data['version_nums'])->find();
                if(!$versions){
                    $data['versions_nums'] =$data['version_nums'];
                    $data['vnums'] = str_replace(['v','V'],['',''],$data['versions']);
                    $data['content'] = $data['version_desc'];
                    $data['ptime'] = strtotime($data['version_ptime']);
                    $data['ctime'] = time();
                    $data['package'] =$this->request->domain().'/data/package/v'.$data['version_nums'].'.zip';
                    $result = Versions::create($data,true);
                    if (!$result) {
                        return callback(0, '版本更新失败');
                    }
                }else{
                    $versions->content=$data['version_desc'];
                    $versions->ptime = strtotime($data['version_ptime']);
                    $versions->package = $this->request->domain().'/data/package/v'.$data['version_nums'].'.zip';
                    $result = $versions->save();
                    if (!$result) {
                        return callback(0, '版本更新失败');
                    }
                }
                $progress=$this->getUpgradeLog(1);
                $now_progress=$progress['progress'];
                $average=bcdiv(2,$data['nums'],2);
                $current_progress=bcadd($now_progress,$average,2);
                $logContent=['progress'=>$current_progress,'desc'=>$data['versions'].'版本更新成功','type'=>0];
                $this->setUpgradeLog($logContent);
                return callback(1, $data['versions'] . '版本更新成功');
            }catch(\Exception $e){
                return callback(0,  $e->getMessage());
            }
        }
        return callback(0, '版本更新失败，请稍后再试');
    }
}
