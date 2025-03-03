<?php

namespace Qstart\Db\QueryBuilder\DML\Traits;

use Qstart\Db\QueryBuilder\DML\CTE\CTE;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;

trait WithTrait
{
    /** @var CTE[] $with */
    protected array $with = [];

    public function addWith(string $alias, SelectQuery $query, bool $recursive = false): self
    {
        $this->with[] = new CTE($alias, $query, $recursive);
        return $this;
    }

    /**
     * @param CTE[] $with
     *
     * @return WithTrait
     */
    public function with(array $with): self
    {
        $this->with = $with;
        return $this;
    }

    /**
     * @return CTE[]
     */
    public function getWith(): array
    {
        return $this->with;
    }
}
