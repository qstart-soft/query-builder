<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Traits\ConditionTrait;
use Qstart\Db\QueryBuilder\DML\Traits\JoiningTrait;
use Qstart\Db\QueryBuilder\DML\Traits\LimitTrait;
use Qstart\Db\QueryBuilder\DML\Traits\TableTrait;
use Qstart\Exception\InvalidArgumentException;

/**
 * Creating a SQL SELECT Statement.
 */
class SelectQuery extends QueryAbstract
{
    use JoiningTrait;
    use TableTrait {
        TableTrait::setTables as from;
    }
    use ConditionTrait;
    use LimitTrait;

    protected array $select = [];
    protected bool $distinct = false;
    protected array $groupBy = [];
    protected array $orderBy = [];
    protected $havingConditions;
    protected $offset = null;
    protected array $unionQueries = [];

    /**
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     * @param mixed $value See README for value format.
     * @return $this
     */
    public function select($value)
    {
        $this->select = [];

        if ($value === null) {
            return $this;
        }

        return $this->addSelect($value);
    }

    /**
     * This is used to add values to the SELECT clause in a SQL statement
     * @param mixed $value See README for value format.
     * @return $this
     */
    public function addSelect($value)
    {
        if (is_array($value)) {
            $this->select = array_merge($this->select, $value);
        } else {
            $this->select[] = $value;
        }

        return $this;
    }

    /**
     * Get values to construct the SELECT clause
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * This is used to add DISTINCT keyword to SELECT clause
     * @param bool $value FALSE to delete and TRUE to add
     * @return $this
     */
    public function distinct(bool $value)
    {
        $this->distinct = $value;
        return $this;
    }

    /**
     * Flag indicating yes or no
     * @return bool
     */
    public function getDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * This is used to construct the GROUP BY clause in a SQL statement.
     * @param mixed $value See README for value format.
     * @return $this
     */
    public function groupBy($value)
    {
        $this->groupBy = [];

        if ($value === null) {
            return $this;
        }

        return $this->addGroupBy($value);
    }

    /**
     * This is used to add values to the GROUP BY clause in a SQL statement.
     * @param mixed $value See README for value format.
     * @return $this
     */
    public function addGroupBy($value)
    {
        if (is_array($value)) {
            $this->groupBy = array_merge($this->groupBy, $value);
        } else {
            $this->groupBy[] = $value;
        }

        return $this;
    }

    /**
     * Array of values for GROUP BY clause
     * @return array
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * This is used to construct the ORDER BY clause in a SQL statement.
     * @param mixed $columns See README for value format.
     * @return $this
     */
    public function orderBy($columns)
    {
        $this->orderBy = [];

        if ($columns === null) {
            return $this;
        }

        return $this->addOrderBy($columns);
    }

    /**
     * This is used to add values to the ORDER BY clause in a SQL statement.
     * @param mixed $columns See README for value format.
     * @return $this
     */
    public function addOrderBy($columns)
    {
        if (is_array($columns)) {
            $this->orderBy = array_merge($this->orderBy, $columns);
        } else {
            $this->orderBy[] = $columns;
        }

        return $this;
    }

    /**
     * Array of values for ORDER BY clause
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * This is used to construct the HAVING clause.
     * This method will replace all conditions if they were previously assigned.
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function having($condition)
    {
        $this->havingConditions = $condition;
        return $this;
    }

    /**
     * This is used to add condition to the HAVING clause.
     * The condition will be added to the current condition using the 'AND' operator.
     *
     * Result: ($this->getConditions()) AND ($condition)
     *
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function andHaving($condition)
    {
        if ($this->havingConditions === null) {
            $this->havingConditions = $condition;
        } else {
            $this->havingConditions = ['and', $this->havingConditions, $condition];
        }

        return $this;
    }

    /**
     * This is used to add condition to the HAVING clause.
     * The condition will be added to the current condition using the 'OR' operator.
     *
     * Result: ($this->getConditions()) OR ($condition)
     *
     * @param string|array|ExprInterface|QueryInterface $condition See README for conditions format.
     * @return $this
     */
    public function orHaving($condition)
    {
        if ($this->havingConditions === null) {
            $this->havingConditions = $condition;
        } else {
            $this->havingConditions = ['or', $this->havingConditions, $condition];
        }

        return $this;
    }

    /**
     * All passed conditions for the HAVING clause.
     * @return mixed
     */
    public function getHavingConditions()
    {
        return $this->havingConditions;
    }

    /**
     * This is used to construct the OFFSET clause.
     *
     * @param int|ExprInterface|SelectQuery|null $offset the offset. Use null or negative value to disable offset.
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Value for OFFSET clause.
     * @return int|ExprInterface|SelectQuery|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * This is used to add union queries. ORDER BY clause will be combined from all queries and added to the end of the union queries
     *
     * @param string|ExprInterface|SelectQuery $query SELECT SQL statement string or instance of SelectQuery or instance of ExprInterface
     * @param bool $all This is used to add `ADD` option
     * @return $this
     */
    public function union($query, bool $all = false)
    {
        $this->unionQueries[] = ['query' => $query, 'all' => $all];
        return $this;
    }

    /**
     * Delete all union queries
     * @return $this
     */
    public function deleteUnion()
    {
        $this->unionQueries = [];
        return $this;
    }

    /**
     * Union queries in format array of arrays look like ['query' => $query, 'all' => $all];
     * @return array
     */
    public function getUnionQueries(): array
    {
        return $this->unionQueries;
    }
}
