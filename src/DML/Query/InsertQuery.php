<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Traits\TableTrait;

/**
 * Creating a SQL INSERT Statement.
 */
class InsertQuery extends QueryAbstract
{
    use TableTrait {
        TableTrait::setTables as into;
    }

    protected $startOfQuery;
    protected $endOfQuery;
    protected array $values = [];

    /**
     * Custom query start
     * @return mixed
     */
    public function getStartOfQuery()
    {
        return $this->startOfQuery;
    }

    /**
     * Change the beginning of the query. The expression `INSERT INTO` will be replaced with the passed expression
     * @param string|ExprInterface $startOfQuery custom expression. For example `INSERT IGNORE INTO`
     * @return InsertQuery
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
     * @return InsertQuery
     */
    public function setEndOfQuery($expression)
    {
        $this->endOfQuery = $expression;
        return $this;
    }

    /**
     * This is used to add group of values to a VALUES clause.
     * @param array|QueryInterface $data The data should be an array in the format \[column1 => value1, ...] or instance of QueryInterface
     * @return $this
     */
    public function addValues($data)
    {
        $this->values[] = $data;
        return $this;
    }

    /**
     * This is used to add multiple groups of values to a VALUES clause.
     * @param array $data The data should be an array of arrays in the format \[column1 => value1, ...]
     * @return $this
     */
    public function addMultipleValues(array $data)
    {
        $data = array_filter($data, fn($datum) => is_array($datum));
        foreach ($data as $datum) {
            $this->addValues($datum);
        }

        return $this;
    }

    /**
     * The groups of values
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
