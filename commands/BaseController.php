<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 16/8/2
 * Time: 下午1:40
 */

namespace app\commands;

use app\models\ConsoleTask;
use app\models\ConsoleTaskLog;
use Yii;
use yii\console\Controller;
use yii\console\Exception;

/**
 * Console基类，继承类的Action方法必须返回bool类型，否则任务结束时无法记录是成功还是失败
 * Class BaseController
 * @package app\console\controllers
 */
class BaseController extends Controller
{
    public $task;
    public $log;

    public function beforeAction($action)
    {
        $program = $action->getUniqueId();
        $task = ConsoleTask::findOne(['program'=>$program]);
        if(!$task){
            throw new Exception("Can not find ".$program." in console tasks. Please check it.");
        }
        $this->task = $task;
        //记录执行日志
        $this->log = new ConsoleTaskLog();
        $this->log->task_id = $this->task->id;
        $this->log->start_time = date("Y-m-d H:i:s");
        $this->task->last_start_time = $this->log->start_time;
        $this->task->status = ConsoleTask::STATUS_STARTED;
        $this->log("Task [".$task->program."] started.");
        $this->task->save();
        return true;
    }

    public function afterAction($action,$result)
    {
        $this->task->last_finish_time = date("Y-m-d H:i:s");
        if($result){
            $this->task->status=ConsoleTask::STATUS_FINISHED;
        }else{
            $this->task->status=ConsoleTask::STATUS_FAILED;
        }
        $this->task->save();
        $this->log->finish_time = $this->task->last_finish_time;
        $this->log("Task [".$this->task->program."] finished.");
        $this->log->save();
    }

    public function log($str)
    {
        $this->log->info.="\n ".date('Y-m-d H:i:s')."---->".$str;
    }
}