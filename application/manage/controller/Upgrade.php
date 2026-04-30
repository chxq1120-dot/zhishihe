<?php

namespace app\manage\controller;

use app\common\lib\UpService;
use think\Controller;
use app\common\model\Versions;
use GuzzleHttp\Client;
use think\Exception;
use think\facade\Env;

/**
 * 版本更新
 */
class Upgrade extends Common
{
    protected $current_version;
    protected $redis;
    public function initialize(){
        parent::initialize();
        $this->model=new Versions();
        $this->current_version=$this->model->order('versions_nums desc')->find();
        $this->redis = new \Redis();
        $this->redis->connect(config('redis.host'), config('redis.port'));
        if (!empty(config('redis.auth'))) {
            $this->redis->auth(config('redis.auth'));
        }
    }
    public function __destruct()
    {
        $this->redis->close();
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
    /**
     * 更新日志
     * @return [type] [description]
     */
    public function upgradeLog()
    {
         //设置过滤方法
         $this->request->filter(['strip_tags']);
         if ($this->request->isAjax()) {
             //如果发送的来源是Selectpage，则转发到Selectpage
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
    /**
     * 显示更新日志
     * @return [type] [description]
     */
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
    /**
     * 获取更新列表包
     */
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

    /**
     * 获取更新进度以及日志信息
     */
    public function getUpgradeLog($type=0)
    {
        $first_log=$this->redis->lrange("upgrade_log",0,0);
        if(empty($first_log)){
            return callback(0, '暂无日志数据');
        }
        $first_log=json_decode($first_log[0],true);
        $data=[
            'progress'=>$first_log['progress'],
            'desc'=>$first_log['desc'],
        ];
        //类型为1直接返回数据
        if($type==1){
            return $data;
        }
        return callback(1, 'success','',$data);
    }
    /**
     * 写入更新进度日志
     */
    protected function setUpgradeLog($logContent)
    {
        try{
            $log_key="upgrade_log";
            if($logContent['type']==1 && $this->redis->exists($log_key)){
                $this->redis->del($log_key);
            }
            $logContent['time']=time();
            $this->redis->lpush($log_key,json_encode($logContent));
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    /**
     * 下载更新包整个过程分配60%
     * @return [type] [description]
     */
    public function updateFile()
    {
        if($this->request->isPost()){
            try{
                if(session_status() === PHP_SESSION_ACTIVE) {
                    session_abort(); // 不保存且不锁定 Session
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
    /**
     * 执行文件更新操作整个过程分配30%
     * @return [type] [description]
     */
    public function copyFile()
    {
        if($this->request->isPost()){
            try{
                if(session_status() === PHP_SESSION_ACTIVE) {
                    session_abort(); // 不保存且不锁定 Session
                }
                $data = input('param.');
                $progress=$this->getUpgradeLog(1);
                $now_progress=$progress['progress'];
                $average=bcdiv(30,$data['nums'],2);
                $zipFileName = 'v' . $data['version_nums'] . '.zip';
                $logContent=['progress'=>$now_progress,'desc'=>'开始复制更新包'.$zipFileName.'文件...','type'=>0];
                $this->setUpgradeLog($logContent);
                #更新包目录
                $zipFilePath = env('root_path') . 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'package';
                #解压目录
                $toZipFilePath= env('root_path') . 'upgrade' . DIRECTORY_SEPARATOR . 'package';
                #执行解压
                $zip = new \ZipArchive();
                if (!$zip->open($zipFilePath . DIRECTORY_SEPARATOR . $zipFileName)) {
                    return callback(0, '解压错误');
                }
                $zip->extractTo($toZipFilePath . DIRECTORY_SEPARATOR . 'v' . $data['version_nums']);
                $zip->close();
                #执行拷贝
                $rootFrom = $toZipFilePath . DIRECTORY_SEPARATOR . 'v' . $data['version_nums'] . DIRECTORY_SEPARATOR . 'frame';
                $rootTo = env('root_path');
                #判断更新包内是否有小程序更新，有则执行删除本地小程序端文件
                $originalPath = $rootFrom . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'mp-weixin';
                #删除冗余文件
                $targetPath = $rootTo . 'frontend'.DIRECTORY_SEPARATOR .'mp-weixin'.DIRECTORY_SEPARATOR.'pages';
                if (is_dir($originalPath)) {
                    removeDir($targetPath);
                }
                #开始复制文件
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
    /**
     * 执行数据更新操作,整个过程分配6%
     * @return [type] [description]
     */
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
    /**
     * 更新版本，整个过程分配2%
     * @return [type] [description]
     */
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