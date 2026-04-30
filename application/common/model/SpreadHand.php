<?php
declare (strict_types=1);

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 资源用户助力表
 */
class SpreadHand extends Model
{
    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
    ];

    //获取器
    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    /**
     * 助力用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id')->setEagerlyType(0)->joinType('left');
    }

    /**
     * 被助力用户
     * @return \think\model\relation\BelongsTo
     */
    public function reuser()
    {
        return $this->belongsTo('User', 'r_uid', 'id')->setEagerlyType(0)->joinType('left');
    }

    /**
     * 被助力资源
     * @return \think\model\relation\BelongsTo
     */
    public function spread()
    {
        return $this->belongsTo('Spread', 'rid', 'id')->setEagerlyType(0)->joinType('left');
    }

    /**
     * 用户助力资源
     * @return array
     */
    public function handSpread($r_uid, $uid, $admin_id, $rid)
    {
        #判断是否超过每日助力次数
        $hand_day_max = intval(config('setting.hand_day_max'));
        if ($hand_day_max > 0) {
            $nums = $this->where(['uid' => $uid])->whereTime('ctime', 'today')->count();
            if ($nums >= $hand_day_max) {
                return [false, '每日助力次数超过限制'];
            }
        }
        #查询是否已经助力过
        $userHande = $this->where(['r_uid' => $r_uid, 'uid' => $uid, 'rid' => $rid])->find();
        if (empty($userHande)) {
            Db::startTrans();
            $data = [
                'uid' => $uid,
                'admin_id' => $admin_id,
                'rid' => $rid,
                'r_uid' => $r_uid,
                'ctime' => time()
            ];
            $result = $this->allowField(true)->save($data);
            if (!$result) {
                return [false, '助力操作异常,助力失败'];
            }
            #更新资源邀请任务信息
            $task = ResourceTask::where('uid', $r_uid)->where('rid', $rid)->find();
            if (!$task) {
                return [false, '助力任务已失效,助力失败'];
            }
            $spread = Spread::where('id', $rid)->field('id,invite_num,exc_video')->find();
            if ($task->invite_num >= $spread->invite_num && ($task->is_video == 1 || $spread->exc_video == 0)) {
                $task->status = 1;
            }
            $task->invite_num = ['inc', 1];
            $task->utime = time();
            $result = $task->save();
            if (!$result) {
                Db::rollback();
                return [false, '助力任务更新错误,助力失败'];
            }
            Db::commit();
            return [true,'助力成功'];
        }
        return [false, '您已经助力过了'];
    }
}