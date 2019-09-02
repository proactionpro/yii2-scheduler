<?php
namespace proaction\scheduler\tests\tasks;

/**
 * Class ErrorTask
 * @package proaction\scheduler\tests\tasks
 */
class ErrorTask extends \proaction\scheduler\Task
{
    public $description = 'Throws an Error';
    public $schedule = '*/1 * * * *';

    public function run()
    {
        trigger_error('this is an error');
    }
}
