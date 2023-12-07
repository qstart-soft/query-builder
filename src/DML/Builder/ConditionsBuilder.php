<?php

namespace Qstart\Db\QueryBuilder\DML\Builder;

use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\QueryInterface;
use Qstart\Db\QueryBuilder\Exception\QueryBuilderException;

class ConditionsBuilder implements BuilderInterface
{
    protected array $params = [];
    protected $dialect;
    protected $conditions;

    /**
     * This builder prepares the conditions for the SQL statement.
     *
     * @param string|array|ExprInterface|QueryInterface $conditions The conditions that needs to be converted to SQL statement
     */
    public function __construct($conditions)
    {
        $this->conditions = $conditions;
    }

    public function build(): Expr
    {
        $this->params = [];
        $sql = $this->buildRecursive($this->conditions);

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

    protected function buildRecursive($conditions): string
    {
        if (!$conditions) {
            return '';
        }

        if (is_string($conditions) || $conditions instanceof ExprInterface) {
            return (string)$this->prepareValue($conditions, false);
        }

        if (is_array($conditions) && !isset($conditions[0])) {
            $expressions = [];
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($conditions as $name => $value) {
                $operator = '=';
                if (is_array($value) || $value instanceof QueryInterface) {
                    $operator = 'IN';
                }
                if (in_array($value, [null, false, true], true)) {
                    $operator = 'IS';
                }

                $expressions[] = sprintf(
                    "%s %s %s",
                    $name,
                    $operator,
                    $this->prepareValue($value, true)
                );
            }
            return implode(' AND ', $expressions);
        }

        if (is_array($conditions)) {
            // operator format: operator, operand 1, operand 2, ...

            $operator = trim(array_shift($conditions));

            switch (strtoupper($operator)) {
                case 'NOT':
                case 'AND':
                case 'OR':
                    $subConditions = [];
                    foreach ($conditions as $i => $operand) {
                        $subConditions[] = $this->buildRecursive($operand);
                    }
                    if (count($subConditions) > 1) {
                        $subConditions = array_map(fn($v) => "($v)", $subConditions);
                    }

                    if (strtoupper($operator) === 'NOT') {
                        $subCondition = implode(' AND ', $subConditions);
                        return "NOT ($subCondition)";
                    } else {
                        $subCondition = implode(" " . strtoupper($operator) . " ", $subConditions);
                    }
                    return $subCondition;
            }
        }

        throw new QueryBuilderException('Invalid conditions in WHERE clause');
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
        $this->params = array_merge($this->params, $params);
    }
}
