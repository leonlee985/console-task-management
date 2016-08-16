<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "console_task".
 *
 * @property integer $id
 * @property string $created_at
 * @property string $created_by
 * @property string $updated_at
 * @property string $updated_by
 * @property string $name
 * @property string $program
 * @property integer $type
 * @property string $start_time
 * @property string $info   任务信息，为CYCLE时，格式为数字，表示每隔N秒执行一次, 为EVERYDAY_FIX_TIME, 格式为H:i:s, 表示每天到时间就执行
 * @property integer $status
 * @property string $last_start_time
 * @property string $last_finish_time
 */
class ConsoleTask extends ActiveRecord
{
    public static $status_list = [
        self::STATUS_NOT_START => "未开始",
        self::STATUS_STARTED => "执行中",
        self::STATUS_FINISHED => "已结束",
        self::STATUS_FAILED => "执行失败",
    ];

    const STATUS_NOT_START = 10;
    const STATUS_STARTED = 20;
    const STATUS_FINISHED = 30;
    const STATUS_FAILED = 40;

    public static $type_list=[
        self::TYPE_ONCE => '执行一次',
        self::TYPE_CYCLE => '循环间隔',
        self::TYPE_EVERYDAY_FIX_TIME => '每日固定时间'
    ];

    const TYPE_ONCE = 10;
    const TYPE_CYCLE =20;
    const TYPE_EVERYDAY_FIX_TIME = 30;

    const DELETED_YES=1;
    const DELETED_NO=0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'console_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name','type', 'status', 'program'], 'required'],
            [['start_time'], 'safe'],
            [['type', 'status'], 'integer'],
            [['created_by', 'updated_by'], 'string', 'max' => 128],
            [['name', 'info', 'program'], 'string', 'max' => 512],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => function () {
                    return date('Y-m-d H:i:s', intval(YII_BEGIN_TIME));
                }
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'created_at' => '创建时间',
            'created_by' => '创建人',
            'updated_at' => '修改时间',
            'updated_by' => '修改人',
            'name' => '任务名称',
            'program' => '执行程序',
            'type' => '任务类型',
            'start_time' => '任务开始时间',
            'info' => '任务信息',
            'status' => '任务状态',
            'last_start_time' => '上次开始执行时间',
            'last_finish_time' => '上次结束执行时间',
        ];
    }
}
