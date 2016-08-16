<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 16/8/2
 * Time: 下午1:40
 */

namespace app\commands;

use app\models\ConsoleTaskLog;
use Yii;
use app\models\ConsoleTask;
use yii\console\Controller;
use yii\console\Exception;

/**
 * 主进程，用于调用其它后台任务
 * Class MainController
 * @package app\console\controllers
 */
class MainController extends Controller
{
    //任务检查间隔时间，单位秒
    const DURATION = 10;
    //最大执行时间
    const MAX_EXEC_TIME=3600;


    public function actionIndex()
    {
        //如果Main进程已经在运行了，则不启动，保持主进程只有一个
        if($this->checkMainProcess())
        {
            echo "Main process is running. Please check it."; exit;
        }
        //每N秒检查一次任务列表
        while (1) {
            $this->checkTasks();
            $this->checkStartedTasks();
            sleep(self::DURATION);
        };
    }

    /**
     * 扫描所有任务
     */
    public function checkTasks()
    {
        echo "\n Checking tasks......";
        $list = ConsoleTask::findAll(["is_deleted" => ConsoleTask::DELETED_NO]);
        foreach ($list as $task) {
            $now=time();
            switch($task->type){
                case ConsoleTask::TYPE_ONCE:
                    if($now>=strtotime($task->start_time) && $task->status==ConsoleTask::STATUS_NOT_START){
                        $this->execTask($task->program);
                    }
                    break;
                case ConsoleTask::TYPE_CYCLE:
                    if($now>=strtotime($task->start_time)){
                        //第一次执行
                        if(empty($task->last_start_time)){
                            $this->execTask($task->program);
                        }else{
                            //间隔执行
                            if($now-strtotime($task->last_start_time)>=intval($task->info)){
                                $this->execTask($task->program);
                            }
                        }
                    }
                    break;
                case ConsoleTask::TYPE_EVERYDAY_FIX_TIME:
                    //如果今天已经成功执行过了，则不执行，否则就执行。
                    if($now>=strtotime($task->start_time) && !( date("Y-m-d",strtotime($task->last_start_time)) == date("Y-m-d") && $task->status == ConsoleTask::STATUS_FINISHED)){
                        //每天超过指定时间便执行
                        $current_time=strtotime(date('H:i:s'));
                        if($current_time>strtotime($task->info)){
                            $this->execTask($task->program);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * 检查已开始的任务
     */
    public function checkStartedTasks()
    {
        echo "\n Checking started tasks......";
        $list = ConsoleTask::findAll(["status" => ConsoleTask::STATUS_STARTED,"is_deleted"=>ConsoleTask::DELETED_NO]);
        $now = time();
        foreach ($list as $task) {
            //检查任务的执行时间，如果已超时，则杀掉任务
            $exec_time = $now-strtotime($task->last_start_time);
            if($exec_time>self::MAX_EXEC_TIME)
            {
                $log = new ConsoleTaskLog();
                $log->task_id=$task->id;
                if($this->killTask($task->program)){
                    $task->status = ConsoleTask::STATUS_FAILED;
                    $task->save();
                    $log->info="\n Task is killed cause timeout. Start Time:".$task->last_start_time. " Kill Time:".date('Y-m-d H:i:s');
                }else{
                    $log->info="\n Tried to kill the task: ".$task->program. " failed, will try it later";
                }
                $log->save();
            }
        }
    }

    /**
     * 执行任务
     * @param $task
     */
    public function execTask($program)
    {
        //现在只支持单进程，如果以后支持多个进程同时运行，需要修改这里的逻辑
        if($this->checkProcess($program)){
            return false;
        }
        $dir = dirname(Yii::$app->getBasePath());
        //将任务放到后台执行
        $cmd = "nohup php ".$dir."/yii " . $program." >/dev/null 2>&1 &";
        echo "\n ".$cmd;
        exec($cmd);
    }

    /**
     * 杀掉任务
     * @param $program
     */
    public function killTask($program)
    {
        //字符太短，避免误杀到其它进程，正常情况不会有这么短的任务名
        if(strlen($program)<4)
            return false;
        $cmd="ps -ef | grep ".$program." | awk '{print $2}' | xargs kill -9";
        exec($cmd);
        return !$this->checkProcess($program);
    }

    /**
     * 检查是否进程中已经有别的主进程
     * @param $process
     * @return bool
     */
    public function checkMainProcess()
    {
        $cmd="ps -ef |grep -v grep|grep -v 'sh -c' | grep main";
        exec($cmd,$output);
        //除开自身如果还有别的进程，则说明主进程已启动了
        return count($output)>1;
    }

    public function checkProcess($process)
    {
        $cmd="ps -ef |grep -v grep|grep -v 'sh -c' | grep ".$process;
        exec($cmd,$output);
        return count($output)>0;
    }

}