<?php


namespace app\models\db;

use app\models\TaskItem;
use app\models\User;
use app\utils\FileUtils;
use app\utils\FirebaseHandler;
use app\utils\MailHandler;
use app\utils\Telegram;
use Throwable;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\debug\models\timeline\Search;
use yii\web\IdentityInterface;

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

}