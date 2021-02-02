<?php
namespace app\commands;

use Yii;
use yii\base\Exception;
use yii\console\Controller;

class RbacController extends Controller
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    public function actionInit(): void
    {
        $auth = Yii::$app->authManager;
        if($auth !== null){
            // добавляем разрешение "create ticket"
            $createTicket = $auth->createPermission('create ticket');
            $createTicket->description = 'Создание заявок на обслуживание';
            $auth->add($createTicket);

            // добавляем разрешение "handle ticket"
            $handleTicket = $auth->createPermission('handle ticket');
            $handleTicket->description = 'Обработка заявок на обслуживание';
            $auth->add($handleTicket);

            // добавляем разрешение "manage ticket"
            $manageTicket = $auth->createPermission('manage ticket');
            $manageTicket->description = 'Модерирование заявок на обслуживание';
            $auth->add($manageTicket);

            // добавляем роль "user" и даём роли разрешение "create ticket"
            $user = $auth->createRole('user');
            $auth->add($user);
            $auth->addChild($user, $createTicket);

            // добавляем роль "handler" и даём роли разрешение создавать и принимать заявки
            // а также все разрешения роли "user"
            $handler = $auth->createRole('handler');
            $auth->add($handler);
            $auth->addChild($handler, $createTicket);
            $auth->addChild($handler, $handleTicket);

            // добавляем роль "manager" и даём роли разрешение создавать и принимать заявки
            // а также все разрешения роли "handler"
            $manager = $auth->createRole('manager');
            $auth->add($manager);
            $auth->addChild($manager, $createTicket);
            $auth->addChild($manager, $handleTicket);
            $auth->addChild($manager, $manageTicket);

            echo 'done';
        }
        else{
            echo 'no auth manager';
        }
    }
}