<?php

namespace Qstart\Db\QueryBuilder\DML\Query;

use Qstart\Db\QueryBuilder\DML\Builder\BuilderInterface;

interface QueryInterface
{
    public function getQueryBuilder(): BuilderInterface;
}
