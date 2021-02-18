<?php


namespace app\utils;

use app\models\db\Email;
use app\models\db\Task;
use app\models\db\FirebaseClient;
use app\models\User;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;

class FirebaseHandler
{
    public static function sendTaskCreated(Task $task): void
    {
        $list = [];
        // отправлю сообщение всем контактам, которые зарегистрированы
        $executors = User::find()->where(['role' => $task->target])->all();
        if (!empty($executors)) {
            /** @var User $executor */
            foreach ($executors as $executor) {
                $contacts = FirebaseClient::find()->where(['person_id' => $executor->id])->all();
                if (!empty($contacts)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $list = array_merge($list, $contacts);
                }
            }
        }

        $message = new Message();
        $message->setPriority('high');
        $message
            ->setData([
                'action' => 'task_created',
                'task_id' => $task->id,
                'initiator' => User::getUserName($task->initiator),
                'task_header' => $task->task_header
            ]);
        self::sendMultipleMessage($list, $message);
    }

    /**
     * @param array $contacts
     * @param Message $message
     */
    private static function sendMultipleMessage(array $contacts, Message $message): void
    {
        if (!empty($contacts) && count($contacts) > 0) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            foreach ($contacts as $contact) {
                $message->addRecipient(new Device($contact->firebase_token));
            }
            $client->send($message);
        }
    }

    /**
     * @param $task Task
     */
    public static function sendTaskAccepted(Task $task): void
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        $initiator = User::findOne($task->initiator);
        if ($initiator !== null) {
            $contacts = FirebaseClient::find()->where(['person_id' => $initiator->id])->all();
            if (!empty($contacts)) {
                $message = new Message();
                $message->setPriority('high');
                $message
                    ->setData([
                        'action' => 'task_accepted',
                        'task_id' => $task->id,
                        'executor' => User::getUserName($task->executor),
                        'task_header' => $task->task_header
                    ]);
                self::sendMultipleMessage($contacts, $message);
            }
        }
    }

    public static function sendTaskCancelled(Task $item): void
    {
        // если задаче назначен исполнитель- отправлю ему сообщение о отмене действия
        if (!empty($item->executor)) {
            $executor = User::findOne($item->executor);
            if ($executor !== null) {
                $contacts = FirebaseClient::find()->where(['person_id' => $executor->id])->all();
                if (!empty($contacts)) {
                    $message = new Message();
                    $message->setPriority('high');
                    $message
                        ->setData([
                            'action' => 'task_cancelled',
                            'task_id' => $item->id,
                            'task_header' => $item->task_header,
                            'initiator' => User::getUserName($item->initiator)
                        ]);
                    self::sendMultipleMessage($contacts, $message);
                }
            }
        }
    }

    public static function sendTaskFinished(Task $item): void
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        $initiator = User::findOne($item->initiator);
        if ($initiator !== null) {
            $contacts = FirebaseClient::find()->where(['person_id' => $initiator->id])->all();
            if (!empty($contacts)) {
                $message = new Message();
                $message->setPriority('high');
                $message
                    ->setData([
                        'action' => 'task_finished',
                        'task_id' => $item->id,
                        'task_header' => $item->task_header
                    ]);
                self::sendMultipleMessage($contacts, $message);
            }
        }
    }

    public static function sendTaskDismissed(Task $item): void
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        $initiator = User::findOne($item->initiator);
        if ($initiator !== null) {
            $contacts = FirebaseClient::find()->where(['person_id' => $initiator->id])->all();
            if (!empty($contacts)) {
                $message = new Message();
                $message->setPriority('high');
                $message
                    ->setData([
                        'action' => 'task_dismissed',
                        'task_id' => $item->id,
                        'reason' => $item->executor_comment,
                        'task_header' => $item->task_header,
                        'executor' => User::getUserName($item->executor)
                    ]);
                self::sendMultipleMessage($contacts, $message);
            }
        }
    }
}