<?php


namespace app\models\db;


use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property string $telegram_id [varchar(255)]
 * @property int $person_id [int(10) unsigned]
 * @property bool $send_debug [tinyint(1)]
 */
class TelegramClient extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'telegram_clients';
    }

    public static function register(User $user, string $token): void
    {
        if (self::find()->where(['person_id' => $user->getId(), 'telegram_id' => $token])->count() === 0) {
            (new self(['person_id' => $user->getId(), 'telegram_id' => $token]))->save();
        }
    }

    /**
     * @param $message
     */
    public static function isRegistered($message): bool
    {
        return (bool)self::find()->where(['telegram_id' => $message->getChat()->getId()])->count();
    }

    /**
     * @param $message
     */
    public static function setGetDebug($message): void
    {
        $item = self::findOne(['telegram_id' => $message->getChat()->getId()]);
        if ($item !== null) {
            $item->send_debug = 1;
            $item->save();
        }
    }

    /**
     * @param $message
     */
    public static function setNotGetDebug($message): void
    {
        $item = self::findOne(['telegram_id' => $message->getChat()->getId()]);
        if ($item !== null) {
            $item->send_debug = 0;
            $item->save();
        }
    }

    /**
     * @return TelegramClient[]
     */
    public static function getSubscribers(): array
    {
        return self::find()->where(['send_debug' => 1])->all();
    }
}