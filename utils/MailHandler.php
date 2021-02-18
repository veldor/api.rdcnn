<?php


namespace app\utils;

use app\models\db\Email;
use app\models\db\Task;
use app\models\User;
use Yii;
use yii\base\Model;

class MailHandler extends Model
{
    public static function getMailText($text): string
    {
        return Yii::$app->controller->renderPartial('/site/mail-template', ['text' => $text]);
    }

    public static function sendMessage($title, $text, $address, $sendTo, $attachments): bool
    {
        $settingsFile = Yii::$app->basePath . '\\priv\\mail_settings.conf';
        if (is_file($settingsFile)) {
            // получу данные
            $content = file_get_contents($settingsFile);
            $settingsArray = mb_split("\n", $content);
            if (count($settingsArray) === 3) {
                $text = self::getMailText($text);
                // отправлю письмо
                $mail = Yii::$app->mailer->compose()
                    ->setFrom([$settingsArray[0] => 'Планировщик РДЦ'])
                    ->setSubject($title)
                    ->setHtmlBody($text)
                    ->setTo([$address => $sendTo ?? '']);
                // попробую отправить письмо, в случае ошибки- вызову исключение
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $mail->attach($attachment[0], ['fileName' => $attachment[1]]);
                    }
                }
                $mail->send();
                return true;
            }
        }
        return false;
    }

    /**
     * @param Email[] $emails
     * @param User $contact
     * @param Task $task
     */
    public static function sendTaskCreatedMail(array $emails, User $contact, Task $task): void
    {
        $personals = GrammarHandler::handlePersonals($contact->name);
        $initiatorInfo = User::findIdentity($task->initiator);
        if ($initiatorInfo !== null) {
            $text = "Пользователь {$initiatorInfo->name} создал задачу <br/>{$task->task_header}<br/>{$task->task_body}<br/>Вы можете принять задачу в приложении или веб-интерфейсе. Успехов!";
            foreach ($emails as $email) {
                self::sendMessage(
                    "Добавлена новая задача",
                    $text,
                    $email->email,
                    $personals,
                    null
                );
            }
        }
    }
}