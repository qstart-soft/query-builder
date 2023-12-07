<?php

namespace Qstart\Db\QueryBuilder\DML\Builder;

use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;

class ValueBuilder implements BuilderInterface
{
    protected array $params = [];
    protected $dialect;

    protected $value;
    protected bool $bindValue;

    /**
     * This builder prepares the values of operations for the SQL statement.
     *
     * @param string|array|ExprInterface|SelectQuery|null|bool $value The expression that needs to be converted to SQL statement
     * @param bool $bindValue Indicator: if a regular string is passed in an expression, then if this is a parameter, then the string will be converted into a named query parameter
     */
    public function __construct($value, bool $bindValue)
    {
        $this->value = $value;
        $this->bindValue = $bindValue;
    }

    public function build(): Expr
    {
        $this->params = [];
        $sql = $this->buildRecursive($this->value, $this->bindValue);

        return new Expr($sql, $this->params);
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

    protected function buildRecursive($value, bool $bindValue): string
    {
        $dialect = $this->getDialect();

        if (is_array($value)) {
            $subExpressions = [];
            foreach ($value as $subExpr) {
                $subExpressions[] = $this->buildRecursive($subExpr, $bindValue);
            }
            $preparedExpr = sprintf("(%s)", implode(', ', $subExpressions));
        } elseif ($value instanceof ExprInterface) {
            $this->addParams($value->getParams());
            $preparedExpr = $value->getExpression($dialect);
        } elseif ($value instanceof SelectQuery) {
            $expr = $value->getQueryBuilder()->setDialect($dialect)->build();
            $preparedExpr = "({$expr->getExpression($dialect)})";
            $this->addParams($expr->getParams());
        } elseif ($value === null) {
            $preparedExpr = "NULL";
        } elseif ($value === false) {
            $preparedExpr = "FALSE";
        } elseif ($value === true) {
            $preparedExpr = "TRUE";
        } else {
            if ($bindValue) {
                $name = BindingParamName::getNextName();
                $this->addParams([$name => $value]);
                $preparedExpr = ":" . $name;
            } else {
                $preparedExpr = (string)$value;
            }
        }

        return (string)$preparedExpr;
    }

    protected function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }
}
