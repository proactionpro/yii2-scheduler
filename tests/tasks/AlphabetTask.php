<?php
namespace proaction\scheduler\tests\tasks;

/**
 * Class AlphabetTask
 * @package proaction\scheduler\tests\tasks
 */
class AlphabetTask extends \proaction\scheduler\Task
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
