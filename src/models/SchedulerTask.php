<?php

namespace proaction\scheduler\models;

use Yii;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * This is the model class for table "scheduler_task".
 */
class SchedulerTask extends \proaction\scheduler\models\base\SchedulerTask
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_PENDING = 10;
    public const STATUS_DUE     = 20;
    public const STATUS_RUNNING = 30;
    public const STATUS_OVERDUE = 40;
    public const STATUS_ERROR   = 50;

    /**
     * @var array
     */
    public const STATUSES = [
        self::STATUS_INACTIVE => 'Inactive',
        self::STATUS_PENDING  => 'Pending',
        self::STATUS_DUE      => 'Due',
        self::STATUS_RUNNING  => 'Running',
        self::STATUS_OVERDUE  => 'Overdue',
        self::STATUS_ERROR    => 'Error',
    ];

    /**
     * Colour map for SchedulerTask status ids
     * @var array
     */
    public const STATUS_COLORS = [
        SchedulerTask::STATUS_PENDING => Console::FG_BLUE,
        SchedulerTask::STATUS_DUE     => Console::FG_YELLOW,
        SchedulerTask::STATUS_OVERDUE => Console::FG_RED,
        SchedulerTask::STATUS_RUNNING => Console::FG_GREEN,
        SchedulerTask::STATUS_ERROR   => Console::FG_RED,
    ];

    /**
     * Return Taskname
     * @return string
     */
    public function __toString()
    {
        return Inflector::camel2words($this->name);
    }

    public function getColor()
    {
        return isset(self::STATUS_COLORS[$this->status_id]) ? self::STATUS_COLORS[$this->status_id] : null;
    }

    /**
     * @param $task
     * @return array|null|SchedulerTask|\yii\db\ActiveRecord
     */
    public static function createTaskModel($task)
    {
        $model = self::find()
            ->where(['name' => $task->getName()])
            ->one();

        if (!$model) {
            $model = new self();
            $model->name = $task->getName();
            $model->active = $task->active;
            $model->next_run = $task->getNextRunDate();
            $model->last_run = NULL;
            $model->status_id = self::STATUS_PENDING;
            $model->description = $task->description;
            $model->schedule = $task->schedule;
            $model->log_file = $task->log_file;
        }
        $model->save(false);
        return $model;
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return isset(self::STATUSES[$this->status_id]) ? self::STATUSES[$this->status_id] : null;
    }


    /**
     * Update the status of the task based on various factors.
     */
    public function updateStatus()
    {
        $status = $this->status_id;
        $isDue = in_array(
            $status,
            [
                self::STATUS_PENDING,
                self::STATUS_DUE,
                self::STATUS_OVERDUE,
            ]
        ) && strtotime($this->next_run) <= time();

        if ($isDue && $this->started_at == null) {
            $status = self::STATUS_DUE;
        } elseif ($this->started_at !== null) {
            $status = self::STATUS_RUNNING;
        } elseif ($this->status_id == self::STATUS_ERROR) {
            $status = $this->status_id;
        } elseif (!$isDue) {
            $status = self::STATUS_PENDING;
        }

        if (!$this->active) {
            $status = self::STATUS_INACTIVE;
        }

        $this->status_id = $status;
    }

    public function beforeSave($insert)
    {
        $this->updateStatus();
        return parent::beforeSave($insert);
    }
}
