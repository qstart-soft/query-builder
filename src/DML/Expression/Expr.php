<?php

namespace Qstart\Db\QueryBuilder\DML\Expression;

final class Expr implements ExprInterface
{
    protected string $expression;
    protected array $params;

    public function __construct(string $expression, array $params = [])
    {
        $this->expression = $expression;
        $this->params = $params;
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
        return false;
    }
}
