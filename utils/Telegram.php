<?php


namespace app\utils;

use app\models\User;
use app\priv\Info;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class Telegram
{
    public static function handleRequest(): void
    {
        try {
            $token = Info::TG_BOT_TOKEN;
            /** @var BotApi|Client $bot */
            $bot = new Client($token);
// команда для start
            $bot->command(/**
             * @param $message Message
             */ 'start', static function ($message) use ($bot) {
                $answer = 'Добро пожаловать! /help для вывода команд';
                /** @var Message $message */
                $bot->sendMessage($message->getChat()->getId(), $answer);
            });

// команда для помощи
            $bot->command('help', static function ($message) use ($bot) {
                try {
                    /** @var Message $message */
                    $answer = 'Команды:
/help - вывод справки';
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                } catch (Exception $e) {
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            });

            $bot->on(/**
             * @param $Update Update
             */ static function ($Update) use ($bot) {
                /** @var Update $Update */
                /** @var Message $message */
                try {
                    $message = $Update->getMessage();
                    $msg_text = $message->getText();
                    // получен простой текст, обработаю его в зависимости от содержимого
                    $answer = self::handleSimpleText($msg_text, $message);
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                } catch (Exception $e) {
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            }, static function () {
                return true;
            });
            try {
                $bot->run();
            } catch (InvalidJsonException $e) {
                // что-то сделаю потом
            }
        } catch (Exception $e) {
            // запишу ошибку в лог

        }
    }


    private static function handleSimpleText(string $msg_text, Message $message):string
    {
        if(str_starts_with($msg_text, '/register ')){
            $token = substr($msg_text, 10);
            $user = User::findIdentityByAccessToken($token);
            if($user !== null){
                return 'found user';
            }
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    public static function sendDebug(string $string)
    {

    }
}