<?php

namespace Qstart\Db\QueryBuilder\DML\CTE;

use InvalidArgumentException;
use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;

class CTE
{
    protected string $alias;
    /** @var SelectQuery|ExprInterface $query */
    protected $query;
    protected bool $recursive;

    public function __construct(
        string $alias,
        $query,
        bool $recursive = false
    ) {
        $this->alias = $alias;
        if (!$query instanceof SelectQuery && !$query instanceof ExprInterface) {
            throw new InvalidArgumentException('Query must be an instance of ' . SelectQuery::class . ' or ' . ExprInterface::class);
        }
        $this->query = $query;
        $this->recursive = $recursive;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return ExprInterface|SelectQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }
}
