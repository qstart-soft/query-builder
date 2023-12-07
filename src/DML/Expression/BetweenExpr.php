<?php

namespace Qstart\Db\QueryBuilder\DML\Expression;

use Qstart\Db\QueryBuilder\DML\Builder\ValueBuilder;

class BetweenExpr implements ExprInterface
{
    protected $expression;
    protected $params;
    protected bool $empty = false;

    public function __construct($expression, $fromValue, $toValue, bool $not = false)
    {
        if ($fromValue === null && $toValue === null) {
            $this->empty = true;
        }

        $expressionExpr = (new ValueBuilder($expression, false))->build();
        $fromExpr = (new ValueBuilder($fromValue, true))->build();
        $toExpr = (new ValueBuilder($toValue, true))->build();

        $this->expression = sprintf(
            '%s %s %s AND %s',
            $expressionExpr->getExpression(),
            $not === true ? 'NOT BETWEEN' : 'BETWEEN',
            $fromExpr->getExpression(),
            $toExpr->getExpression(),
        );

        $this->params = array_merge(
            $expressionExpr->getParams(),
            $fromExpr->getParams(),
            $toExpr->getParams()
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
