<?php
namespace proactionpro\scheduler\tests\tasks;

/**
 * Class AlphabetTask
 * @package proactionpro\scheduler\tests\tasks
 */
class AlphabetTask extends \proactionpro\scheduler\Task
{
    public $description = 'Prints the alphabet';
    public $schedule = '*/1 * * * *';

    public function run()
    {
        foreach (range('A', 'Z') as $letter) {
            echo $letter;
        }
    }
}
