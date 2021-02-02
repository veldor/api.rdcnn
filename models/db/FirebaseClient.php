<?php


namespace app\models\db;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property string $firebase_token [varchar(255)]
 * @property int $person_id [int(10) unsigned]
 */
class FirebaseClient extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'firebase_clients';
    }

    public static function add(int $id, string $firebaseToken): void
    {
        (new self(['person_id' => $id, 'firebase_token' => $firebaseToken]))->save();
    }
}