<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Builder\BuilderInterface;
use Qstart\Db\QueryBuilder\DML\Builder\QueryBuilder;
use Qstart\Db\QueryBuilder\Expression;

abstract class QueryAbstract implements QueryInterface
{
    public function getQueryBuilder(): BuilderInterface
    {
        return new QueryBuilder($this);
    }
}
