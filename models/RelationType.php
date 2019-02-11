<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "relation_type".
 *
 * @property int $id
 * @property string $name
 */
class RelationType extends \yii\db\ActiveRecord
{
    public static $QINZI = 1;
    public static $PEIOU = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'relation_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
        ];
    }

    /**
     * @return array
     */
    public static function getRelationTypeList()
    {
        $result = [];
        $list = RelationType::find()->all();
        if (!empty($list)) {
            $result = ArrayHelper::map($list, 'id', 'name');
        }
        return $result;
    }
}