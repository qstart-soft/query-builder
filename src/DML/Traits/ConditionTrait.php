<?php

namespace Qstart\Db\QueryBuilder\DML\Traits;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\QueryInterface;

trait ConditionTrait
{
    protected $conditions;

    /**
     * This is used to construct the WHERE clause.
     * This method will replace all conditions if they were previously assigned.
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function where($condition)
    {
        $this->conditions = $condition;
        return $this;
    }

    /**
     * This is used to add condition to the WHERE clause.
     * The condition will be added to the current condition using the 'AND' operator.
     *
     * Result: ($this->getConditions()) AND ($condition)
     *
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function andWhere($condition)
    {
        if ($this->conditions === null) {
            $this->conditions = $condition;
        } else {
            $this->conditions = ['and', $this->conditions, $condition];
        }

        return $this;
    }

    /**
     * This is used to add condition to the WHERE clause.
     * The condition will be added to the current condition using the 'OR' operator.
     *
     * Result: ($this->getConditions()) OR ($condition)
     *
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function orWhere($condition)
    {
        if ($this->conditions === null) {
            $this->conditions = $condition;
        } else {
            $this->conditions = ['or', $this->conditions, $condition];
        }

        return $this;
    }

    /**
     * This is an analogue of the method `where()`,
     * with the difference that all conditions with a value equal to NULL and expressions for which the `isEmpty()` returns null will be excluded from the conditions
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function filterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->where($condition);
        }

        return $this;
    }

    /**
     * This is an analogue of the method `andWhere()`,
     * with the difference that all conditions with a value equal to NULL and expressions for which the `isEmpty()` returns null will be excluded from the conditions
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function andFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->andWhere($condition);
        }

        return $this;
    }

    /**
     * This is an analogue of the method `orWhere()`,
     * with the difference that all conditions with a value equal to NULL and expressions for which the `isEmpty()` returns null will be excluded from the conditions
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function orFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->orWhere($condition);
        }

        return $this;
    }

    /**
     * All passed conditions for the WHERE clause
     * @return mixed
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    protected function filterCondition($condition)
    {
        if ($condition instanceof ExprInterface && $condition->isEmpty()) {
            return [];
        }

        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                foreach ($condition as $i => $operand) {
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }
                break;
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    protected function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }
}
