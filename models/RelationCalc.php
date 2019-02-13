<?php

namespace app\models;

use app\models\Person;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class RelationCalc extends Model
{
    public $base;
    public $target;
    private $base_person;
    private $target_person;
    private $name_query = '';
    private $order = -1;
    private $second_order = -1;

    public static $ORDER = ['幺', '大', '二', '三', '四', '五', '六', '七', '八', '九', '十',];

    public function rules()
    {
        return [
            [['base', 'target'], 'required'],
            [['base'], 'exist', 'skipOnError' => true, 'targetClass' => Person::className(), 'targetAttribute' => ['base' => 'id']],
            [['target'], 'exist', 'skipOnError' => true, 'targetClass' => Person::className(), 'targetAttribute' => ['target' => 'id']],
            ['base', 'compare', 'compareAttribute' => 'target', 'operator' => '!='],
            ['target', 'compare', 'compareAttribute' => 'base', 'operator' => '!='],
        ];
    }

    public function attributeLabels()
    {
        return [
            'base' => '起点',
            'target' => '终点',
        ];
    }

    /**
     * checked
     * @return array|bool
     */
    public function getName()
    {
        if (!$this->name_query) {
            return false;
        }
        $name_calc = new NameCalc();
        $name_calc->query_code = $this->name_query;
        $name = $name_calc->calculateName();

        if ($name['error_level'] == 0) {
            if ($this->order == -1) {
                $order_str = '';
            } else {
                $order_str = RelationCalc::$ORDER[$this->order];
            }
            if ($this->second_order != -1) {
                $order_str = RelationCalc::$ORDER[$this->second_order];
            }
            $name['data'] = str_replace('%number%', $order_str, $name['data']);
            $name['data'] = str_replace('%order%', $order_str, $name['data']);
            $name['data'] = str_replace('%second_number%', $order_str, $name['data']);
            $name['data'] = str_replace('%second_order%', $order_str, $name['data']);
            $name_str = '<strong>' . $this->base_person . '</strong>是<strong>' . $this->target_person . '</strong>的<strong>' . $name['data'] . '</strong>。';
        } else if ($name['error_level'] == 1) {
            $name_str = '无法计算称呼。但是根据辈分可以叫做<strong>' . $name['data'] . '</strong>。';
        } else {
            $name_str = '无法计算称呼。';
        }
        return [
            'data' => $name_str,
            'error_level' => $name['error_level'],
        ];
    }

    /**
     * checked
     * @return string
     */
    public function getRelation()
    {
        $this->base_person = Person::findOne($this->base)->full_name;
        $this->target_person = Person::findOne($this->target)->full_name;

        $relation_graph = [];
        $cost = [];
        $path = [];
        $mark = [];

        $all_person = Person::find()->all();
        foreach ($all_person as $person) {
            $relation_graph[$person['id']] = [];
            $cost[$person['id']] = INF;
            $mark[$person['id']] = false;
        }
        foreach ($all_person as $person) {
            $children = $person->children;
            foreach ($children as $cld_a) {
                foreach ($children as $cld_b) {
                    if ($cld_a != $cld_b) {
                        $type_1 = null;
                        $type_2 = null;
                        switch ($cld_a->gender) {
                            case Gender::$MALE:
                                $type_1 = '兄弟';
                                break;
                            case Gender::$FEMALE:
                                $type_1 = '姐妹';
                                break;
                        }
                        switch ($cld_b->gender) {
                            case Gender::$MALE:
                                $type_2 = '兄弟';
                                break;
                            case Gender::$FEMALE:
                                $type_2 = '姐妹';
                                break;
                        }
                        if ($type_1) {
                            array_push($relation_graph[$cld_a->id], [$cld_b->id, $type_1]);
                        }
                        if ($type_2) {
                            array_push($relation_graph[$cld_b->id], [$cld_a->id, $type_2]);
                        }
                    }
                }
            }
        }

        $all_relations = Relation::find()->asArray()->all();
        foreach ($all_relations as $relation) {
            $parent = Person::findOne($relation['parent']);
            $child = Person::findOne($relation['child']);
            $type_1 = '未知';
            $type_2 = '未知';
            switch ($relation['type']) {
                case RelationType::$QINZI:
                    switch ($parent->gender) {
                        case Gender::$MALE:
                            $type_1 = '父亲';
                            break;
                        case Gender::$FEMALE:
                            $type_1 = '母亲';
                            break;
                        default:
                            $type_1 = '父母';
                    }
                    switch ($child->gender) {
                        case Gender::$MALE:
                            $type_2 = '儿子';
                            break;
                        case Gender::$FEMALE:
                            $type_2 = '女儿';
                            break;
                        default:
                            $type_2 = '子女';
                    }
                    break;
                case RelationType::$FUQI:
                    $type_1 = '丈夫';
                    $type_2 = '妻子';
                    break;
            }
            array_push($relation_graph[$relation['parent']], [$relation['child'], $type_1]);
            array_push($relation_graph[$relation['child']], [$relation['parent'], $type_2]);
        }

        $current = $this->base;
        $mark[$current] = true;
        $path[$current] = [null, null];
        $cost[$current] = 0;
        $counter = 0;

        while (true) {
            $counter++;

            foreach ($relation_graph[$current] as $node) {
                if (!$mark[$node[0]]) {
                    $dist = 1 + $cost[$current];
                    if ($dist < $cost[$node[0]]) {
                        $cost[$node[0]] = $dist;
                        $path[$node[0]] = [$current, $node[1]];
                    }
                }
            }

            $min_v = INF;
            $min_k = -1;
            foreach ($cost as $k => $v) {
                if ($v < $min_v && !$mark[$k]) {
                    $min_k = $k;
                    $min_v = $v;
                }
            }
            $current = $min_k;
            $mark[$min_k] = true;
            if ($mark[$this->target]) {
                break;
            }
            if ($counter > 1000) {
                break;
            }
        }

        if ($cost[$this->target] == INF) {
            return '<strong>' . $this->base_person . '</strong>与<strong>' . $this->target_person . '</strong>没有联系。';
        };

        $result = '<strong>' . $this->base_person . '</strong>是<strong>' . $this->target_person . '</strong>';
        $current = $this->target;
        $is_fuqi = false;
        $last_node = -1;
        while ($path[$current][0]) {
            if ($this->name_query != -1) {
                $name_type = NameType::findOne(['name' => $path[$current][1]]);
                if ($name_type) {
                    $this->name_query .= $name_type->id;
                } else {
                    $this->name_query = -1;
                }
            }

            $result .= '的' . $path[$current][1];
            $is_fuqi = $path[$current][1] == '丈夫' || $path[$current][1] == '妻子';
            $current = $path[$current][0];
            if ($path[$current][0]) {
                $last_node = $current;
            }
        }

        if ($is_fuqi) {
            $this->second_order = RelationCalc::getOrder($last_node);
        } else {
            $this->order = RelationCalc::getOrder($this->base);
        }

        return $result . '。';
    }

    public static function getOrder($base)
    {
        $base_gender = Person::findOne($base)->gender;

        $parents = Relation::find()
            ->select('parent')
            ->where(['child' => $base, 'type' => RelationType::$QINZI])
            ->asArray()
            ->aLL();
        $parents = ArrayHelper::getColumn($parents, 'parent');

        $siblings = Relation::find()
            ->select('child')
            ->leftJoin('person', 'relation.child = person.id')
            ->where(['parent' => $parents, 'type' => RelationType::$QINZI, 'gender' => $base_gender])
            ->groupBy('relation.child')
            ->orderBy('person.birth_date')
            ->asArray()
            ->all();

        if ($siblings && count($siblings) != 1) {
            $siblings = ArrayHelper::getColumn($siblings, 'child');
            $siblings = array_flip($siblings);
            $order = $siblings[$base] + 1;
            if ($order == count($siblings)) {
                $order = 0;
            }
            return $order;
        }

        return -1;
    }
}