<?php


namespace app\models\db;

use app\models\TaskItem;
use app\models\User;
use app\utils\FileUtils;
use app\utils\FirebaseHandler;
use app\utils\MailHandler;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\web\IdentityInterface;

/**
 * @property int $id [int(10) unsigned]
 * @property int $initiator [int(10) unsigned]
 * @property int $target [int(10) unsigned]
 * @property int $executor [int(10) unsigned]
 * @property int $task_creation_time [int(10) unsigned]
 * @property int $task_accept_time [int(10) unsigned]
 * @property int $task_planned_finish_time [int(10) unsigned]
 * @property int $task_finish_time [int(10) unsigned]
 * @property string $task_header [varchar(255)]
 * @property string $task_body [varchar(255)]
 * @property string $task_status [varchar(255)]
 * @property string $executor_comment [varchar(255)]
 */
class Task extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'tasks';
    }

    /**
     * @param int $id
     * @param null $filter
     * @param null $sort
     * @param bool $revertSort
     * @param null $limit
     * @param null $page
     * @return TaskItem[]
     */
    public static function getTaskList(int $id, $filter = null, $sort = null, $revertSort = false, $limit = null, $page = null): array
    {
        $query = self::find()->where(['initiator' => $id]);
        $incomingFilterValue = [];
        if ($filter !== null) {
            $incomingFilterValue = self::constructFilter($filter, $incomingFilterValue);
            $query->andWhere(['task_status' => $incomingFilterValue]);
        }
        if ($sort !== null) {
            switch ($sort) {
                case "0":
                    if ($revertSort) {
                        $query->orderBy('task_status DESC');
                    } else {
                        $query->orderBy('task_status');
                    }
                    break;
                case "1":
                    if ($revertSort) {
                        $query->orderBy('task_header DESC');
                    } else {
                        $query->orderBy('task_header');
                    }
                    break;
                case "2":
                    if ($revertSort) {
                        $query->orderBy('task_creation_time DESC');
                    } else {
                        $query->orderBy('task_creation_time');
                    }
                    break;
                case "3":
                    if ($revertSort) {
                        $query->orderBy('target DESC');
                    } else {
                        $query->orderBy('target');
                    }
                    break;
                case "4":
                    if ($revertSort) {
                        $query->orderBy('task_finish_time DESC');
                    } else {
                        $query->orderBy('task_finish_time');
                    }
                    break;
            }
        }
        if ($limit !== null) {
            $query->limit($limit);
        } else {
            $limit = 0;
        }
        if ($page !== null) {
            $query->offset($limit * $page);
        }
        $existent = $query->all();
        $result = [];
        if (!empty($existent)) {
            /** @var Task $item */
            foreach ($existent as $item) {
                $result[] = self::getTaskItem($item);
            }
        }
        return $result;
    }

    /**
     * @param Task $item
     * @return TaskItem
     */
    public static function getTaskItem(Task $item): TaskItem
    {
        $task = new TaskItem();
        $task->id = $item->id;
        $initiator = User::findIdentity($item->initiator);
        if ($initiator !== null) {
            $task->initiator = $initiator->name;
            $task->initiatorEmail = Email::getFirstEmail($initiator) ?? '';
            $task->initiatorPhone = Phone::getFirstPhone($initiator) ?? '';
        }
        if (!empty($item->executor)) {
            $executor = User::findIdentity($item->executor);
            if ($executor !== null) {
                $task->executor = $executor->name;
                $task->executorEmail = Email::getFirstEmail($executor) ?? '';
                $task->executorPhone = Phone::getFirstPhone($executor) ?? '';
            }
        } else {
            $task->executor = '';
        }
        /** @var Role $target */
        $target = Role::findOne($item->target);
        if ($target !== null) {
            $task->target = $target->role_name;
        }
        $task->task_creation_time = $item->task_creation_time;
        $task->task_accept_time = $item->task_accept_time ?: 0;
        $task->task_planned_finish_time = $item->task_planned_finish_time ?: 0;
        $task->task_finish_time = $item->task_finish_time ?: 0;
        $task->task_header = $item->task_header ?: '';
        $task->task_body = $item->task_body;
        $task->task_status = $item->task_status;
        $task->executor_comment = $item->executor_comment ?: '';
        $task->imageFile = FileUtils::isImage($item);
        $task->attachmentFile = FileUtils::isAttachment($item);
        return $task;
    }

    /**
     * @param $taskId
     * @return TaskItem
     * @throws \Exception
     */
    public static function getTaskInfo($taskId): TaskItem
    {
        $item = self::findOne($taskId);
        if ($item !== null) {
            return self::getTaskItem($item);
        }
        throw new Exception("Неверный идентификатор задачи");
    }

    public static function setTaskConfirmed($taskId, $daysForFinish, User $user): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && $item->task_status === 'created' && $user->role === $item->target) {
            $now = time();
            $item->task_accept_time = $now;
            $item->task_planned_finish_time = $now + $daysForFinish * 86400;
            $item->executor = $user->id;
            $item->task_status = 'accepted';
            $item->save();
            // отправлю сообщение инициатору о том, что задача принята
            FirebaseHandler::sendTaskAccepted($item);
        }
    }

    public static function setTaskCancelled($taskId, IdentityInterface $user): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && ($item->task_status === 'created' || $item->task_status === 'accepted') && $item->initiator === $user->id) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'cancelled_by_initiator';
            $item->save();
            FirebaseHandler::sendTaskCancelled($item);
        }
    }

    public static function findNew(User $user): int
    {
        return self::find()->where(['target' => $user->role, 'task_status' => 'created'])->count();
    }

    /**
     * @param $taskId
     * @param IdentityInterface $executor
     */
    public static function setTaskFinished($taskId, IdentityInterface $executor): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && $item->task_status !== 'finished' && $item->executor === $executor->getId()) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'finished';
            $item->save();
            // отправлю сообщение инициатору о том, что задача принята
            FirebaseHandler::sendTaskFinished($item);
        }
    }

    public static function setTaskDismissed($taskId, $reason, IdentityInterface $user): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && $item->task_status === 'accepted' && $item->executor === $user->getId()) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'cancelled_by_executor';
            $item->executor_comment = $reason;
            $item->save();
            // отправлю сообщение инициатору о том, что задача отменена
            FirebaseHandler::sendTaskDismissed($item);
        }
    }

    public static function getTasksForExecutor(User $user): array
    {
        return self::find()->where(['executor' => $user->id])->orWhere(['executor' => null, 'target' => $user->role])->all();
    }

    /**
     * @param $taskId
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function deleteTask($taskId): void
    {
        if (!empty($taskId)) {
            $task = self::findOne($taskId);
            if ($task !== null) {
                $task->delete();
            }
        }
    }

    public static function getTotalTasksCount(int $id, $filter = null): int
    {

        $query = self::find()->where(['initiator' => $id]);
        if ($filter !== null) {
            $incomingFilterValue = [];
            $incomingFilterValue = self::constructFilter($filter, $incomingFilterValue);
            $query->andWhere(['task_status' => $incomingFilterValue]);
        }
        return $query->count();
    }

    public static function getUnhandledTasksCount(): string
    {
        // посчитаю общее количество непринятых заявок
        $unhandledTasks = self::find()->where(['task_status' => 'created'])->count();
        if ($unhandledTasks > 0) {
            return "<span class=\"badge badge-danger\">$unhandledTasks</span>";
        }
        return '';
    }

    public static function getOverdueTasksCount(): string
    {
// посчитаю количество просроченных заявок
        $unhandledTasks = self::find()->where(['task_finish_time' => null])->andWhere(['<', 'task_planned_finish_time', time()])->count();
        if ($unhandledTasks > 0) {
            return "<span class=\"badge badge-danger\">$unhandledTasks</span>";
        }
        return '';
    }

    /**
     * @param $filter
     * @param array $incomingFilterValue
     * @return array
     */
    public static function constructFilter($filter, array $incomingFilterValue): array
    {
        $filterArray = str_split($filter);
        if ($filterArray[0] === '1') {
            $incomingFilterValue[] = "created";
        }
        if ($filterArray[1] === '1') {
            $incomingFilterValue[] = "accepted";
        }
        if ($filterArray[2] === '1') {
            $incomingFilterValue[] = "finished";
        }
        if ($filterArray[3] === '1') {
            $incomingFilterValue[] = "cancelled_by_initiator";
        }
        if ($filterArray[4] === '1') {
            $incomingFilterValue[] = "cancelled_by_executor";
        }
        return $incomingFilterValue;
    }

    public function setExecutor($executorId): void
    {
        if ($this->task_status === 'created') {
            $executor = User::findIdentity($executorId);
            if ($executor !== null) {
                $this->executor = $executorId;
                $this->task_status = 'accepted';
                $this->task_accept_time = time();
                $this->save();
                FirebaseHandler::sendTaskDelegated($this);
                FirebaseHandler::sendTaskAccepted($this);
                MailHandler::sendTaskDelegatedMail($this);
            }
        }
    }

    public function redirectTo($targetId): void
    {
        if ($this->task_status === 'created') {
            $this->target = $targetId;
            $this->save();
            FirebaseHandler::sendTaskCreated($this);
            Email::sendTaskCreated($this);
        }
    }

    /**
     * @return string
     */
    public function getExecutor(): string
    {
        if ($this->executor !== null) {
            return User::getUserName($this->executor);
        }
        return '';
    }
}