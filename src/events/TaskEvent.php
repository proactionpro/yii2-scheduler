<?php


namespace proaction\scheduler\events;

use yii\base\Event;


class TaskEvent extends Event
{
    public $task;
    public $exception;
    public $success;

    public $cancel = false;
}
