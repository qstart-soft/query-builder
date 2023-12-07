<?php

namespace Qstart\Db\QueryBuilder\DML\Traits;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;

trait LimitTrait
{
    protected $limit = null;

    /**
     * This is used to construct the LIMIT clause.
     *
     * @param int|ExprInterface|SelectQuery|null $offset the limit. Use null or negative value to disable limit.
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Value for LIMIT clause.
     * @return int|ExprInterface|SelectQuery|null
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
