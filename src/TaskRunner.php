<?php
namespace proaction\scheduler;

use Yii;
use proaction\scheduler\events\TaskEvent;
use proaction\scheduler\models\SchedulerLog;
use proaction\scheduler\models\SchedulerTask;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
 * Class TaskRunner
 *
 * @package proaction\scheduler
 * @property Task $task
 */
class TaskRunner extends \yii\base\Component
{
    /**
     * Indicates whether an error occured during the executing of the task.
     * @var string
     */
    public $error;

    /**
     * The task that will be executed.
     *
     * @var Task
     */
    private $_task;

    /** @var SchedulerLog */
    private $_log;

    /** @var bool */
    private $running = false;

    /** @var ?string */
    private $defaultLogFile;

    public function init()
    {
        parent::init();
        $this->defaultLogFile = Yii::$app->params['defaultSchedulerLogFile'] ?? null;
    }

    /**
     * @param Task $task
     */
    public function setTask(Task $task)
    {
        $this->_task = $task;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->_task;
    }

    /**
     * @param SchedulerLog $log
     */
    public function setLog($log)
    {
        $this->_log = $log;
    }

    /**
     * @return SchedulerLog
     */
    public function getLog()
    {
        return $this->_log;
    }

    /**
     * @param bool $forceRun
     * @return string
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function runTask($forceRun = false): string
    {
        $task = $this->getTask();

        if ($task->shouldRun($forceRun)) {
            $event = new TaskEvent([
                'task' => $task,
                'success' => true,
            ]);
            $this->trigger(Task::EVENT_BEFORE_RUN, $event);
            if (!$event->cancel) {
                $task->start();
                ob_start();
                try {
                    $this->running = true;
                    $this->shutdownHandler();
                    $task->run();
                    $this->running = false;
                    $output = ob_get_clean();
                    $this->log($output);
                    $task->stop();
                } catch (\Exception $e) {
                    $this->running = false;
                    $task->exception = $e;
                    $event->exception = $e;
                    $event->success = false;
                    $this->handleError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                }
                $this->trigger(Task::EVENT_AFTER_RUN, $event);
            }
        }
        $task->getModel()->save();
        return $output ?? 'Задача не была запущена.';
    }

    /**
     * If the yii error handler has been overridden with `\proaction\scheduler\ErrorHandler`,
     * pass it this instance of TaskRunner, so it can update the state of tasks in the event of a fatal error.
     */
    public function shutdownHandler()
    {
        $errorHandler = Yii::$app->getErrorHandler();

        if ($errorHandler instanceof \proaction\scheduler\ErrorHandler) {
            Yii::$app->getErrorHandler()->taskRunner = $this;
        }
    }

    /**
     * @param $code
     * @param $message
     * @param $file
     * @param $lineNumber
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function handleError($code, $message, $file, $lineNumber)
    {
        echo sprintf('ERROR: %s %s', $code, PHP_EOL);
        echo sprintf('ERROR FILE: %s %s', $file, PHP_EOL);
        echo sprintf('ERROR LINE: %s %s', $lineNumber, PHP_EOL);
        echo sprintf('ERROR MESSAGE: %s %s', $message, PHP_EOL);

        // if the failed task was mid transaction, rollback so we can save.
        if (null !== ($tx = \Yii::$app->db->getTransaction())) {
            $tx->rollBack();
        }

        $output = ob_get_clean();

        $this->error = $output;
        $this->log($output);
        $this->getTask()->getModel()->status_id = SchedulerTask::STATUS_ERROR;
        $this->getTask()->stop();
    }

    /**
     * @param string $output
     */
    public function log($output)
    {
        $model = $this->getTask()->getModel();
        $logFile = $model->log_file ?: $this->defaultLogFile;
        if ($logFile) {
            try {
                if (!file_exists($logFile)) {
                    FileHelper::createDirectory(dirname($logFile), 0777);
                    if (touch($logFile)) {
                        chmod($logFile, 0666);
                    }
                }
                if ($h = @fopen($logFile, 'a')) {
                    fwrite($h, $output . PHP_EOL);
                    fclose($h);
                } else {
                    throw new Exception('Log file is not available or cannot be created.');
                }
            } catch (Exception $e) {
                $output = $e->getMessage() . PHP_EOL . $output;
            }
        }
        $log = $this->getLog();
        $log->started_at = $model->started_at;
        $log->ended_at = date('Y-m-d H:i:s');
        $log->error = $this->error ? 1 : 0;
        $log->output = $output;
        $log->scheduler_task_id = $model->id;
        $log->save(false);
    }
}
