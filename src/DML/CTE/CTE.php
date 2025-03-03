<?php

namespace Qstart\Db\QueryBuilder\DML\CTE;

use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;

class CTE
{
    protected string $alias;
    protected SelectQuery $query;
    protected bool $recursive;

    public function __construct(
        string $alias,
        SelectQuery $query,
        bool $recursive = false
    ) {
        $this->alias = $alias;
        $this->query = $query;
        $this->recursive = $recursive;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }
}
