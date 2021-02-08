<?php
/** @noinspection PhpUndefinedMethodInspection */


namespace app\utils;

use app\models\db\TelegramClient;
use app\models\Management;
use app\models\User;
use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use Yii;

class Telegram
{

    private static Client $bot;
    private static Message $message;

    public static function handleRequest(): void
    {

        Telegram::sendDebug("access to tg " . serialize($_POST));
        try {
            $token = Info::TG_BOT_TOKEN;
            self::$bot = new Client($token);
// команда для start
            self::$bot->command(/**
             * @param $message Message
             */ 'start', static function ($message) {
                self::$message = $message;
                $answer = 'Добро пожаловать! /help для вывода команд';
                /** @var Message $message */
                self::sendMessage($answer);
            });

// команда для помощи
            self::$bot->command('help', static function ($message){
                self::$message = $message;
                if (TelegramClient::isRegistered($message)) {
                    $answer = 'Команды:
/help - вывод справки
/get_debug - получать события отладки
/not_get_debug - не получать события отладки
/version - версия ПО сервера
/update_software - версия ПО сервера
/my_tasks - просмотр задач
';
                } else {
                    $answer = 'Команды:
/help - вывод справки';
                }
                /** @var Message $message */
                self::sendMessage($answer);
            });

            self::$bot->command('get_debug', static function ($message) {
                self::$message = $message;
                if(TelegramClient::isRegistered(self::$message)){
                    TelegramClient::setGetDebug(self::$message);
                    self::sendMessage('Теперь вы будете получать отладочную информацию');
                }
            });
            self::$bot->command('not_get_debug', static function ($message) {
                self::$message = $message;
                if(TelegramClient::isRegistered(self::$message)){
                    TelegramClient::setNotGetDebug(self::$message);
                    self::sendMessage('Теперь вы будете получать отладочную информацию');
                }
            });
            self::$bot->command('version', static function ($message) {
                self::$message = $message;
                if(TelegramClient::isRegistered(self::$message)){
                    $versionFile = Yii::$app->basePath . '\\version.inf';
                    if(is_file($versionFile)){
                        self::sendMessage("Версия ПО: " . file_get_contents($versionFile));
                    }
                }
            });
            self::$bot->command('update_software', static function ($message) {
                self::$message = $message;
                if(TelegramClient::isRegistered(self::$message)){
                    Management::updateSoft();
                    self::sendMessage('Запущено обновление ПО');
                }
            });

            self::$bot->on(/**
             * @param $Update Update
             */ static function ($Update) {
                /** @var Update $Update */
                /** @var Message $message */
                try {
                    self::$message = $Update->getMessage();
                    $msg_text = self::$message->getText();
                    // получен простой текст, обработаю его в зависимости от содержимого
                    $answer = self::handleSimpleText($msg_text);
                    self::sendMessage($answer);
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage() . $e->getLine());
                }
            }, static function () {
                return true;
            });


            try {
                self::$bot->run();
            } catch (InvalidJsonException) {
                // что-то сделаю потом
            }
        } catch (Exception $e) {
            echo 'have error ' . $e->getTraceAsString();
            // запишу ошибку в лог
            self::sendDebug($e->getMessage());
        }
    }


    private static function handleSimpleText(string $msg_text): string
    {
        if (str_starts_with($msg_text, '/register ')) {
            $token = substr($msg_text, 10);
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                // зарегистрирую пользователя, если он ещё не зарегистрирован
                TelegramClient::register($user, self::$message->getChat()->getId());
                return 'Успешная регистрация';
            }
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    public static function sendDebug(string $message): void
    {
        $token = Info::TG_BOT_TOKEN;
        self::$bot = new Client($token);
        $subscribers = TelegramClient::getSubscribers();
        if (!empty($subscribers)) {
            foreach ($subscribers as $item) {
                self::sendMessageToReceiver($item->telegram_id, $message);
            }
        }
    }

    private static function sendMessage($messageText): void
    {
        self::$bot->sendMessage(self::$message->getChat()->getId(), $messageText);
    }

    private static function sendMessageToReceiver($receiver, $messageText): void
    {
        self::$bot->sendMessage($receiver, $messageText);
    }
}