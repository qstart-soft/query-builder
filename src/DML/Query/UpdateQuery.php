<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Traits\ConditionTrait;
use Qstart\Db\QueryBuilder\DML\Traits\JoiningTrait;
use Qstart\Db\QueryBuilder\DML\Traits\LimitTrait;
use Qstart\Db\QueryBuilder\DML\Traits\TableTrait;

/**
 * Creating a SQL UPDATE Statement.
 */
class UpdateQuery extends QueryAbstract
{
    use TableTrait {
        TableTrait::setTables as setTable;
    }
    use JoiningTrait;
    use ConditionTrait;
    use LimitTrait;

    protected array $joinFrom = [];
    protected array $attributes = [];
    protected $startOfQuery;
    protected $endOfQuery;

    /**
     * Custom query start
     * @return mixed
     */
    public function getStartOfQuery()
    {
        return $this->startOfQuery;
    }

    /**
     * Change the beginning of the query. The expression `UPDATE` will be replaced with the passed expression
     * @param string|ExprInterface $startOfQuery custom expression. For example `UPDATE ONLY`
     * @return $this
     */
    public function setStartOfQuery($expression)
    {
        $this->startOfQuery = $expression;
        return $this;
    }

    /**
     * Custom query end
     * @return mixed
     */
    public function getEndOfQuery()
    {
        return $this->endOfQuery;
    }

    /**
     * Change the ending of the query. The expression will be added to the end of the query
     * @param string|ExprInterface $endOfQuery custom expression. For example `RETURNING id`
     * @return $this
     */
    public function setEndOfQuery($expression)
    {
        $this->endOfQuery = $expression;
        return $this;
    }

    /**
     * This is used to construct the FROM clause in a SQL statement.
     * @param string|array|ExprInterface $table See README for table formats.
     * @return $this
     * @throws \Exception
     */
    public function joinFrom($table)
    {
        $this->joinFrom = $this->normalizeTables($table);
        return $this;
    }

    /**
     * This is used to construct the SET clause in a SQL statement.
     * @param array|string|ExprInterface|QueryInterface $attributes See README for attributes format.
     * @return $this
     */
    public function set($attributes)
    {
        $this->attributes = [];
        $this->addSet($attributes);

        return $this;
    }

    /**
     * Add attributes to the SET clause.
     * @param array|string|ExprInterface|QueryInterface $attributes See README for attributes format.
     * @return $this
     */
    public function addSet($attributes)
    {
        if ($attributes) {
            if (is_array($attributes)) {
                foreach ($attributes as $name => $value) {
                    $this->attributes[$name] = $value;
                }
            } else {
                $this->attributes[] = $attributes;
            }
        }

        return $this;
    }

    /**
     * Get a list of attributes for the SET clause.
     * @return array Array of attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the table for FROM clause.
     * @return array
     */
    public function getJoinFrom(): array
    {
        return $this->joinFrom;
    }
}
