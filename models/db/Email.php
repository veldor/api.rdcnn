<?php


namespace app\models\db;


use app\models\User;
use app\utils\MailHandler;
use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $email [varchar(255)]
 * @property int $person_id [int(10) unsigned]
 */

class Email extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'emails';
    }

    /**
     * @param User $user
     * @return string|null
     */
    public static function getFirstEmail(User $user):?string
    {
        $existentEmail = self::getEmail($user);
        if($existentEmail !== null){
            return $existentEmail->email;
        }
        return null;
    }
    /**
     * @param User $user
     * @return Email|null
     */
    public static function getEmail(User $user):?Email
    {
        return self::findOne(['person_id' => $user->getId()]);
    }

    public static function addEmail(User $user, string $address): void
    {
        $existentValue = self::getEmail($user);
        if($existentValue === null){
            (new self(['email' => $address, 'person_id' => $user->id]))->save();
        }
        else{
            $existentValue->email = $address;
            $existentValue->save();
        }
    }

    public static function getEmailButton(string $initiatorId): string
    {
        $initiator = User::findIdentity($initiatorId);
        if($initiator !== null){
            $email = self::getFirstEmail($initiator);
            if($email !== null){
                return "<a class='btn btn-default' href='mailto:$email'><span class='glyphicon glyphicon-envelope
 text-info'></span> <b class='text-info'>Написать</b></a>";
            }
        }
        return '';
    }

    public static function sendTaskCreated(Task $task)
    {
        // найду все контакты, состоящие в подразделении, которому назначена задача
        $contacts = User::findByGroup($task->target);
        if(!empty($contacts)){
            foreach ($contacts as $contact) {
                $emails = self::findAll(['person_id' => $contact->id]);
                if(!empty($emails)){
                    MailHandler::sendTaskCreatedMail($emails,$contact, $task);
                }
            }
        }
    }
}