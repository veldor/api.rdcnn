<?php


namespace app\models\db;

use app\models\User;
use app\utils\Telegram;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property int $taskId [int(10) unsigned]
 * @property int $claimerId [int(10) unsigned]
 * @property string $claimText [text]
 * @property string $state [text]
 * @property int $claimTime [int(10) unsigned]
 */
class Claim extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'claims';
    }

    public static function createClaim(User $user, mixed $taskId, mixed $claimText): void
    {
        $task = Task::findOne($taskId);
        if($task !== null && !empty($claimText)){
            if($user->id === $task->initiator || (!empty($task->executor) && $task->executor === $user->id)){
                $newClaim = new self;
                $newClaim->claimText = $claimText;
                $newClaim->taskId = $task->id;
                $newClaim->claimerId = $user->id;
                $newClaim->claimTime = time();
                $newClaim->state = 'waiting';
                $newClaim->save();
                Telegram::sendDebug("Оставлена жалоба: " . $newClaim->claimText);
            }
        }
    }

    public static function countOpened()
    {
        // посчитаю общее количество непринятых заявок
        $opened = self::find()->where(['state' => 'waiting'])->count();
        if ($opened > 0) {
            return "<span class=\"badge badge-danger\">$opened</span>";
        }
        return '';
    }

}