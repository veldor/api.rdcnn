<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 *
 * @property int $id [int(10) unsigned]
 * @property string $username [varchar(255)]  Номер обследования
 * @property string $password_hash [varchar(255)]  Хеш пароля
 * @property bool $failed_try [tinyint(4)]  Неудачных попыток входа
 * @property string $access_token [varchar(255)]  Токен доступа
 * @property int $last_login_try [bigint(20)]  Дата последней попытки входа
 * @property int $role [int(10) unsigned]
 * @property string $name [varchar(255)]
 * @property string $auth_key [char(255)]  Ключ аутентификации
 */

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName():string
    {
        return 'person';
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): User|null
    {
        return self::findOne($id);
    }


    /**
     * Finds identity by access token
     * @param mixed $token
     * @param null $type
     * @return User|null
     */
    public static function findIdentityByAccessToken(mixed $token, $type = null): ?User
    {
        return self::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return User|null
     */
    public static function findByUsername(string $username): ?User
    {
        return self::findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->auth_key;
    }


    /**
     * @param string $authKey
     * @return bool
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
}
