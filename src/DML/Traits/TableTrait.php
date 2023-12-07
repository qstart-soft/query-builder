<?php

namespace Qstart\Db\QueryBuilder\DML\Traits;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\QueryInterface;
use Qstart\Db\QueryBuilder\Exception\QueryBuilderException;

trait TableTrait
{
    protected $firstAlias;
    protected array $normalizedTables = [];

    /**
     * This is used to assign an alias to the first table
     * @param string $alias
     * @return $this
     */
    public function alias(string $alias)
    {
        if (count($this->normalizedTables)) {
            $this->normalizedTables = array_merge(
                [$alias => $this->normalizedTables[array_key_first($this->normalizedTables)]],
                array_splice($this->normalizedTables, 1)
            );
            $this->firstAlias = null;
        } else {
            $this->firstAlias = $alias;
        }

        return $this;
    }

    /**
     * This is used to construct the FROM clause or to construct another main`s table clause in a SQL statement.
     * @param string|array|ExprInterface $tables See README for table formats.
     * @return $this
     */
    public function setTables($tables)
    {
        $this->normalizedTables = $this->normalizeTables($tables);
        if ($this->firstAlias) {
            $this->alias($this->firstAlias);
        }
        return $this;
    }

    /**
     * An array of normalised tables in the format [alias => table, table, ...]
     * @return array
     */
    public function getNormalizedTables(): array
    {
        return $this->normalizedTables;
    }

    /**
     * An array of tables in the format [alias => table, table, ...]
     * @param $tables
     * @return array
     * @throws QueryBuilderException
     */
    protected function normalizeTables($tables): array
    {
        if (!is_array($tables)) {
            $tables = [$tables];
        }

        $normalizedTables = [];
        foreach ($tables as $alias => $table) {
            $normalizedTables = array_merge($normalizedTables, $this->normalizeTable($table, $alias));
        }

        return $normalizedTables;
    }

    /**
     * A table in the format [alias => table] or [table]
     * @param $table
     * @param $alias
     * @return array
     * @throws QueryBuilderException
     */
    protected function normalizeTable($table, $alias): array
    {
        $alias = is_string($alias) ? $alias : null;

        if (is_string($table) || $table instanceof ExprInterface || $table instanceof QueryInterface) {
            return $alias ? [$alias => $table] : [$table];
        }

        throw new QueryBuilderException("Invalid table`s format");
    }
}
