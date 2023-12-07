<?php

namespace Qstart\Db\QueryBuilder\DML\Expression;

use Qstart\Db\QueryBuilder\DML\Builder\ValueBuilder;

/**
 * Expression for comparison using comparison operators
 */
class CompareExpr implements ExprInterface
{
    protected $expression;
    protected $params;
    protected bool $empty = false;

    /**
     * Examples of usage:
     * new CompareExpr('!=', 'id', 2);
     * new CompareExpr('>=', 'created_at', new Expr('now()'));
     *
     * @param string $operator comparison operator
     * @param mixed $expression Expression comparison
     * @param mixed $value Value comparison
     */
    public function __construct($operator, $expression, $value)
    {
        if ($value === null) {
            $this->empty = true;
        }

        $leftExpr = (new ValueBuilder($expression, false))->build();
        $rightExpr = (new ValueBuilder($value, true))->build();

        $this->expression = sprintf(
            '%s %s %s',
            $leftExpr->getExpression(),
            $operator,
            $rightExpr->getExpression()
        );

        $this->params = array_merge(
            $leftExpr->getParams(),
            $rightExpr->getParams(),
        );
    }

    /**
     * @param mixed $dialect
     * @inheritDoc
     */
    public function getExpression($dialect = null): string
    {
        return $this->expression;
    }

    /**
     * @inheritDoc
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }
}
