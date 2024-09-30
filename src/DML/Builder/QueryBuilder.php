<?php

namespace Qstart\Db\QueryBuilder\DML\Builder;

use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\DeleteQuery;
use Qstart\Db\QueryBuilder\DML\Query\InsertQuery;
use Qstart\Db\QueryBuilder\DML\Query\QueryInterface;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;
use Qstart\Db\QueryBuilder\DML\Query\UpdateQuery;
use Qstart\Db\QueryBuilder\Exception\QueryBuilderException;

/**
 * SQL query builder
 */
class QueryBuilder implements BuilderInterface
{
    protected QueryInterface $query;

    protected array $params = [];
    protected $dialect;

    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    public function setDialect($dialect): BuilderInterface
    {
        $this->dialect = $dialect;
        return $this;
    }

    public function getDialect()
    {
        return $this->dialect;
    }

    public function build(): Expr
    {
        $this->params = [];
        $sql = '';

        if ($this->query instanceof SelectQuery) {
            $sql = $this->buildSelectQuery();
        } elseif ($this->query instanceof DeleteQuery) {
            $sql = $this->buildDeleteQuery();
        } elseif ($this->query instanceof UpdateQuery) {
            $sql = $this->buildUpdateQuery();
        } elseif ($this->query instanceof InsertQuery) {
            $sql = $this->buildInsertQuery();
        }

        return new Expr($sql, $this->params);
    }

    protected function buildSelectQuery(): string
    {
        /** @var SelectQuery $query */
        $query = $this->query;
        $unionQueries = $query->getUnionQueries();

        $sql = "SELECT {$this->buildSelect()}";

        $from = $this->buildTable($query->getNormalizedTables());
        $from && $sql .= " FROM $from";

        $joining = $this->buildJoining();
        $joining && $sql .= " $joining";

        $where = $this->buildCondition($query->getConditions());
        $where && $sql .= " WHERE $where";

        $groupBy = $this->buildGroupBy();
        $groupBy && $sql .= " $groupBy";

        $having = $this->buildCondition($query->getHavingConditions());
        $having && $sql .= " HAVING $having";

        if (!$unionQueries) {
            $orderBy = $this->buildOrderBy();
            $orderBy && $sql .= " $orderBy";
        }

        $limit = $this->buildLimit();
        $limit && $sql .= " LIMIT $limit";

        $offset = $query->getOffset();
        if ($offset !== null) {
            $offset = $this->prepareValue($offset, false);
            $offset !== null && $sql .= " OFFSET $offset";
        }

        if ($unionQueries) {
            foreach ($unionQueries as ['query' => $unionQuery, 'all' => $allOption]) {
                $unionExpr = null;
                if ($unionQuery instanceof SelectQuery) {
                    $query->addOrderBy($unionQuery->getOrderBy());
                    $unionQuery->orderBy(null);
                    $unionExpr = $unionQuery->getQueryBuilder()->setDialect($this->getDialect())->build();
                } elseif (is_string($unionQuery)) {
                    $unionExpr = new Expr($unionQuery);
                } elseif ($unionQuery instanceof ExprInterface) {
                    $unionExpr = $unionQuery;
                }

                if (null !== $unionExpr) {
                    $sql .= sprintf(
                        ' %s %s',
                        $allOption === true ? 'UNION ALL' : 'UNION',
                        $unionExpr->getExpression($this->getDialect()),
                    );
                    $this->addParams($unionExpr->getParams());
                }
            }

            $orderBy = $this->buildOrderBy();
            $orderBy && $sql .= " $orderBy";
        }

        return $sql;
    }

    protected function buildDeleteQuery(): string
    {
        /** @var DeleteQuery $query */
        $query = $this->query;

        $start = $this->prepareValue($query->getStartOfQuery() ?: 'DELETE FROM', false);

        $sql = "$start {$this->buildTable($query->getNormalizedTables())}";

        $using = $this->buildTable($query->getUsing());
        $using && $sql .= " USING $using";

        $joining = $this->buildJoining();
        $joining && $sql .= " $joining";

        $where = $this->buildCondition($query->getConditions());
        $where && $sql .= " WHERE $where";

        $limit = $this->buildLimit();
        $limit && $sql .= " LIMIT $limit";

        $end = $this->prepareValue($query->getEndOfQuery() ?: '', false);
        $end && $sql .= " $end";

        return $sql;
    }

    protected function buildUpdateQuery(): string
    {
        /** @var UpdateQuery $query */
        $query = $this->query;

        $start = $this->prepareValue($query->getStartOfQuery() ?: 'UPDATE', false);

        $sql = "$start {$this->buildTable($query->getNormalizedTables())}";

        $set = $this->buildUpdateSet();
        $set && $sql .= " SET $set";

        $query->getJoinFrom() && $sql .= " FROM {$this->buildTable($query->getJoinFrom())}";

        $joining = $this->buildJoining();
        $joining && $sql .= " $joining";

        $where = $this->buildCondition($query->getConditions());
        $where && $sql .= " WHERE $where";

        $limit = $this->buildLimit();
        $limit && $sql .= " LIMIT $limit";

        $end = $this->prepareValue($query->getEndOfQuery() ?: '', false);
        $end && $sql .= " $end";

        return $sql;
    }

    protected function buildInsertQuery(): string
    {
        /** @var InsertQuery $query */
        $query = $this->query;

        $start = $this->prepareValue($query->getStartOfQuery() ?: 'INSERT INTO', false);

        $sql = "$start {$this->buildTable($query->getNormalizedTables())} {$this->buildInsertValues()}";

        $end = $this->prepareValue($query->getEndOfQuery() ?: '', false);
        $end && $sql .= " $end";

        return $sql;
    }

    protected function buildSelect(): string
    {
        /** @var SelectQuery $query */
        $query = $this->query;

        $select = '*';
        if (!empty($query->getSelect())) {
            $attrs = [];
            foreach ($query->getSelect() as $alias => $expr) {
                $preparedExpr = $this->prepareValue($expr, false);
                if (is_numeric($alias) || $alias === '') {
                    $attrs[] = $preparedExpr;
                } elseif (is_string($alias)) {
                    $attrs[] = "$preparedExpr AS $alias";
                }
            }
            $select = implode(', ', $attrs);
        }
        if ($query->getDistinct() === true) {
            $select = "DISTINCT $select";
        }

        return $select;
    }

    protected function buildTable($tables): string
    {
        if ($tables) {
            if (is_string($tables)) {
                $tables = [$tables];
            }

            $values = [];

            if (is_array($tables)) {
                foreach ($tables as $alias => $table) {
                    $table = $this->prepareValue($table, false);

                    $values[] = is_string($alias) && $alias ? "$table AS $alias" : "$table";
                }
            }

            if ($values) {
                return implode(', ', $values);
            }
        }

        return '';
    }

    protected function buildJoining(): string
    {
        /** @var SelectQuery|UpdateQuery $query */
        $query = $this->query;
        $joiningList = [];
        if ($query->getJoiningList()) {
            foreach ($query->getJoiningList() as $item) {
                if (array_key_exists('type', $item) && array_key_exists('table', $item) && array_key_exists('conditions', $item)) {
                    $joiningList[] = sprintf(
                        '%s %s ON %s',
                        $item['type'],
                        $this->buildTable($item['table']),
                        $this->buildCondition($item['conditions'])
                    );
                }
            }
        }

        return implode(' ', $joiningList);
    }

    protected function buildGroupBy(): string
    {
        /** @var SelectQuery $query */
        $query = $this->query;

        $list = [];
        if ($query->getGroupBy()) {
            foreach ($query->getGroupBy() as $expr) {
                $list[] = $this->prepareValue($expr, false);
            }
        }

        return $list ? 'GROUP BY ' . implode(', ', $list) : '';
    }

    protected function buildOrderBy(): string
    {
        /** @var SelectQuery $query */
        $query = $this->query;

        $list = [];
        if ($query->getOrderBy()) {
            foreach ($query->getOrderBy() as $key => $value) {
                if (in_array($value, [SORT_ASC, SORT_DESC], true)) {
                    $expr = $this->prepareValue($key, false);
                    $list[] = $expr . ($value === SORT_DESC ? " DESC" : " ASC");
                } elseif ($value) {
                    $list[] = $this->prepareValue($value, false);
                }
            }
        }

        return $list ? 'ORDER BY ' . implode(', ', $list) : '';
    }

    protected function buildLimit(): string
    {
        /** @var SelectQuery|UpdateQuery|DeleteQuery $query */
        $query = $this->query;

        $limit = $query->getLimit();
        if ($limit !== null) {
            $limit = $this->prepareValue($limit, false);
            return $limit;
        }

        return '';
    }

    protected function buildUpdateSet(): string
    {
        /** @var UpdateQuery $query */
        $query = $this->query;

        if ($query->getAttributes()) {
            $attrs = [];
            foreach ($query->getAttributes() as $name => $value) {
                if ($value) {
                    if (is_string($name)) {
                        $value = $this->prepareValue($value, true);
                        $attrs[] = "$name = $value";
                    } else {
                        $value = $this->prepareValue($value, false);
                        $attrs[] = "$value";
                    }
                }
            }

            return implode(', ', $attrs);
        }

        return '';
    }

    protected function buildInsertValues(): string
    {
        /** @var InsertQuery $query */
        $query = $this->query;

        $values = array_values($query->getValues());

        if (count($values) === 1 && $values[0] instanceof SelectQuery) {
            return $this->prepareValue($values[0], false);
        }

        // Получаем столбцы и кэшируем их в виде строки
        $columns = array_keys($values[0]);
        $sqlColumns = implode(', ', $columns);

        // Начинаем строить SQL для значений
        $sqlValues = [];
        foreach ($values as $row) {
            if ($row instanceof QueryInterface) {
                throw new QueryBuilderException('SELECT statements with another values in VALUES clause are not supported');
            }
            // Собираем значения в правильном порядке без использования array_merge
            $orderedRow = [];
            foreach ($columns as $column) {
                $orderedRow[] = $this->prepareValue($row[$column] ?? null, true);
            }

            $sqlValues[] = '(' . implode(', ', $orderedRow) . ')';
        }

        return "($sqlColumns) VALUES " . implode(', ', $sqlValues);
    }

    protected function buildCondition($conditions): string
    {
        $builder = new ConditionsBuilder($conditions);
        $expr = $builder->setDialect($this->getDialect())->build();
        $this->addParams($expr->getParams());

        return $expr->getExpression($this->getDialect());
    }

    protected function prepareValue($value, bool $bindValue): string
    {
        $builder = new ValueBuilder($value, $bindValue);
        $expr = $builder->setDialect($this->getDialect())->build();
        $this->addParams($expr->getParams());

        return $expr->getExpression($this->getDialect());
    }

    protected function addParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }
    }
}
