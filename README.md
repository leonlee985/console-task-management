
================================
# console-task-management Yii2后台任务管理

##设计思路： 
后台启动主进程，主进程负责监控及调用任务列表。
任务类型暂时分为3种，单次，重复间隔，每日固定时间
单次任务不管成功失败，只执行一次
重复间隔任务，按设定的间隔时间调用，如果任务仍在执行，则忽略。
每日固定时间任务，每日按指定的时间执行，如果执行失败，将重试。
所有的任务在执行时都会检测是否该任务仍然在执行(进程仍然存在), 如果是，则不会执行。确保单任务，单进程。
任务如果执行时间过长，则会启动超时机制，立即杀掉该任务进程。

##使用方法:
主进程配置在Crontab中，每隔1分钟执行一次。（主进程会自己先检查是否已在调用，如果没有才会启动）
在console/controllers里编写Controller，并继承BaseController.
在actionXXX里编写业务逻辑，注意在关键业务的地方使用$this->log()写入日志. 并在最后返回true/false 标明任务成功或失败。(如果不返回true,则会默认任务完成状态为失败)
在界面里新建一个任务，填写名称，执行程序(使用在console里执行的名称eg, controller/action), 选择任务类型以及开始时间。保存。一个执行程序只能在一个任务里，如果两个任务填写了同一个任务则会报错。

##部分截图:
<img src="https://github.com/leonlee985/console-task-management/blob/master/img/index.png" alt="图片名称" align=center />

<img src="https://github.com/leonlee985/console-task-management/blob/master/img/new.png"  width = "320" height = "276" alt="图片名称" align=center />

<img src="https://github.com/leonlee985/console-task-management/blob/master/img/console-log.png" alt="图片名称" align=center />
