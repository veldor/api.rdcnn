<?php


namespace app\models\db;


use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $number [varchar(11)]
 * @property int $person_id [int(10) unsigned]
 */

class Phone extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'phone_numbers';
    }

    /**
     * @param User $user
     * @return string|null
     */
    public static function getFirstPhone(User $user):?string
    {
        $existent = self::getPhone($user);
        if($existent !== null){
            $existentNumber = $existent->number;
            if(str_starts_with($existentNumber, '7')){
                $existentNumber = '+' . $existentNumber;
            }
            return $existentNumber;
        }
        return null;
    }
    /**
     * @param User $user
     * @return Email|null
     */
    public static function getPhone(User $user):?Phone
    {
        return self::findOne(['person_id' => $user->getId()]);
    }

    public static function addPhone(User $user, string $phone): void
    {
        $existentValue = self::getPhone($user);
        if($existentValue === null){
            (new self(['number' => $phone, 'person_id' => $user->id]))->save();
        }
        else{
            $existentValue->number = $phone;
            $existentValue->save();
        }
    }

    public static function getPhoneButton(string $initiatorId): string
    {
        $initiator = User::findIdentity($initiatorId);
        if($initiator !== null){
            $phone = self::getFirstPhone($initiator);
            if($phone !== null){
                return "<a class='btn btn-default' href='tel:$phone'><span class='glyphicon glyphicon-phone text-success'></span> <b class='text-success'>Позвонить</b></a><a class='btn btn-default' href='viber://chat?number=$phone'><span class='glyphicon glyphicon-phone text-success'></span> <b class='text-success'>Viber</b></a>";
            }
        }
        return '';
    }
}