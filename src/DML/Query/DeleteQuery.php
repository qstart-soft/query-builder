<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Traits\ConditionTrait;
use Qstart\Db\QueryBuilder\DML\Traits\JoiningTrait;
use Qstart\Db\QueryBuilder\DML\Traits\LimitTrait;
use Qstart\Db\QueryBuilder\DML\Traits\TableTrait;

/**
 * Creating a SQL DELETE Statement.
 */
class DeleteQuery extends QueryAbstract
{
    use JoiningTrait;
    use TableTrait {
        TableTrait::setTables as from;
    }
    use ConditionTrait;
    use LimitTrait;

    protected array $using = [];
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
     * Change the beginning of the query. The expression `DELETE FROM` will be replaced with the passed expression
     * @param string|ExprInterface $startOfQuery custom expression. For example `DELETE FROM ONLY`
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
     * This is used to construct the USING clause in a SQL statement.
     * @param string|array|ExprInterface $table See README for table formats.
     * @return $this
     */
    public function using($table)
    {
        if ($table) {
            $this->using = $this->normalizeTables($table);
        } else {
            $this->using = [];
        }
        return $this;
    }

    /**
     * Get an array of values for a USING clause
     * @return array
     */
    public function getUsing()
    {
        return $this->using;
    }
}
