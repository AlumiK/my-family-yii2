<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $result array */
/* @var $relation_types array */

$this->title = '称呼计算器';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="calc-relation">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="calc-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'query_str')->textInput(['id' => 'query-str', 'readonly' => 'true', 'value' => '我']) ?>

        <?= $form->field($model, 'query')->textInput(['id' => 'query', 'type' => 'hidden'])->label(false) ?>

        <div class="form-group">
            <?php foreach ($relation_types as $k => $v): ?>
                <?= Html::button($v, ['class' => 'btn btn-default', 'onclick' => 'appendRelation(' . ($k + 1) . ')']); ?>
            <?php endforeach; ?>
            <?= Html::button('删除', ['class' => 'btn btn-danger', 'onclick' => 'deleteRelation()']) ?>
            <?= Html::button('清空', ['class' => 'btn btn-danger', 'onclick' => 'clearRelation()']) ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton('计算', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <script type="application/javascript">
            let relation_types = <?= json_encode($relation_types) ?>;

            window.onload = () => {
                refreshQueryStr();
            };

            function appendRelation(relation) {
                let query = $('#query');
                query.val(query.val() + relation);
                refreshQueryStr();
            }

            function refreshQueryStr() {
                let query_str = '我';
                let query = $('#query').val().split('');
                for (let relation of query) {
                    query_str += '的' + relation_types[parseInt(relation) - 1];
                }
                $('#query-str').val(query_str);
            }

            function deleteRelation() {
                let query = $('#query');
                if (query.val()) {
                    query.val(query.val().substring(0, query.val().length - 1));
                }
                refreshQueryStr();
            }

            function clearRelation() {
                $('#query').val('');
                refreshQueryStr();
            }
        </script>

        <style type="text/css">
            @media (max-width: 489px) {
                .btn {
                    margin-top: 3px;
                }
            }
        </style>

    </div>

    <div class="calc-result">

        <?php if ($result['error_level'] == 0): ?>
        <div class="panel panel-default">
            <?php elseif ($result['error_level'] == 1): ?>
            <div class="panel panel-warning">
                <?php else: ?>
                <div class="panel panel-danger">
                    <?php endif; ?>
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            计算结果
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p><?= $result['data'] ?></p>
                    </div>
                </div>
            </div>

            <p>注：计算优先选择查询条件没有经过的人，例如“我的父亲的儿子”是“我的兄弟”，而不是“本人”（因为“本人”已经经过了）。</p>

        </div>
    </div>
