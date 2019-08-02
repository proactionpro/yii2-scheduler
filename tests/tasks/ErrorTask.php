<?php
namespace proactionpro\scheduler\tests\tasks;

/**
 * Class ErrorTask
 * @package proactionpro\scheduler\tests\tasks
 */
class ErrorTask extends \proactionpro\scheduler\Task
{
    public $description = 'Throws an Error';
    public $schedule = '*/1 * * * *';

    public function run()
    {
        trigger_error('this is an error');
    }
}
