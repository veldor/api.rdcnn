<?php


namespace app\models\db;


use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $item_name [varchar(64)]
 * @property string $user_id [varchar(64)]
 * @property int $created_at [int(11)]
 */

class AuthAssigment extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'auth_assignment';
    }

    public static function deleteUserRights(User $existentIdentity)
    {
        $rights = self::findAll(['user_id' => $existentIdentity->getId()]);
        if(!empty($rights)){
            foreach ($rights as $item) {
                $item->delete();
            }
        }
    }
}