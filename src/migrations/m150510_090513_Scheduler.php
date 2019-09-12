<?php

use yii\db\Schema;
use yii\db\Migration;

class m150510_090513_Scheduler extends Migration
{
    const TABLE_SCHEDULER_LOG = '{{%scheduler_log}}';
    const TABLE_SCHEDULER_TASK = '{{%scheduler_task}}';

    public function safeUp()
    {
        $this->createTable(self::TABLE_SCHEDULER_LOG, [
            'id' => $this->primaryKey(),
            'scheduler_task_id' => $this->integer()->notNull(),
            'started_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'ended_at' => $this->timestamp()->defaultValue(null),
            'output' => $this->text()->notNull(),
            'error' => $this->boolean()->defaultValue(false),
        ]);

        $this->createTable(self::TABLE_SCHEDULER_TASK, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'schedule' => $this->string()->notNull(),
            'description' => $this->text()->notNull(),
            'status_id' => $this->integer()->notNull(),
            'started_at' => $this->timestamp()->null()->defaultValue(null),
            'last_run' => $this->timestamp()->null()->defaultValue(null),
            'next_run' => $this->timestamp()->null()->defaultValue(null),
            'active' => $this->boolean()->notNull()->defaultValue(false),
            'log_file' => $this->text()->null()->defaultValue(null),
        ]);
        $this->addForeignKey('fk_scheduler_log_scheduler_task_id', self::TABLE_SCHEDULER_LOG, 'scheduler_task_id', self::TABLE_SCHEDULER_TASK, 'id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_scheduler_log_scheduler_task_id', self::TABLE_SCHEDULER_LOG);
        $this->dropTable(self::TABLE_SCHEDULER_LOG);
        $this->dropTable(self::TABLE_SCHEDULER_TASK);
    }
}
