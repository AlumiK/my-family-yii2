<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\RelationCalc;
use app\models\NameCalc;
use app\models\CalendarCalc;

class CalcController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @return string
     */
    public function actionRelation()
    {
        $model = new RelationCalc();
        $relation_result = ['error_level' => -1];
        $name_result = ['error_level' => -1];

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $relation_result = RelationCalc::formatRelationResult($model->getRelation());
            $name_result = NameCalc::formatNameResult($model->getName());
        }

        return $this->render('relation', [
            'model' => $model,
            'relation_result' => $relation_result,
            'name_result' => $name_result,
        ]);
    }

    /**
     * @return string
     */
    public function actionName()
    {
        $model = new NameCalc();
        $result = ['error_level' => -1];
        $relation_types = NameCalc::$name_types;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $result = NameCalc::formatNameResult($model->getName());
        } else {
            $model->gender = -1;
            $model->type = 'default';
        }

        return $this->render('name', [
            'model' => $model,
            'result' => $result,
            'relation_types' => $relation_types,
        ]);
    }

    /**
     * @return string
     */
    public function actionCalendar()
    {
        $model = new CalendarCalc();
        $result = ['error_level' => -1];

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $result = $model->getDateData();
        } else {
            $model->type = 'solar';
        }

        return $this->render('calendar', [
            'model' => $model,
            'result' => $result,
        ]);
    }
}
