<?php


namespace app\models\db;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $role_name [varchar(255)]
 * @property string $role_description [varchar(255)]
 */

class Role extends ActiveRecord
{
    public static function tableName()
    {
        return 'roles';
    }

    public static function getList()
    {
        $answer = [];
        $roles = self::find()->all();
        if(!empty($roles)){
            foreach($roles as $role){
                $answer[$role->id] = $role->role_name;
            }
        }
        return $answer;
    }
}