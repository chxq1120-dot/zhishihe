<?php

namespace app\common\model;
use think\facade\Env;
use think\Model;
class Word extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
    ];
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //取得全部参数
    public function setWord()
    {
        $path=Env::get('config_path').'/word.php';
        $list= $this->where('status',1)->order('indexid asc')->select();
        $str = "<?php return [\n\r";
        foreach($list as $k=>$v){
            $str .= "\t{$k}=>'{$v['name']}',".PHP_EOL;
        }
        $str .= '];';
        $res=file_put_contents($path,$str);
        if(!$res){
           return false;
        }
        return true;
    }
}