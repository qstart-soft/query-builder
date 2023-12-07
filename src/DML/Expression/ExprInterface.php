<?php

namespace Qstart\Db\QueryBuilder\DML\Expression;

interface ExprInterface
{
    /**
     * The DB expression
     * @param mixed $dialect SQL dialect
     * @return string
     */
    public function getExpression($dialect = null): string;

    /**
     * List of parameters that should be bound for this expression.
     * The keys are placeholders appearing in expression and the values are the corresponding parameter values.
     * @return array
     */
    public function getParams(): array;

    /**
     * The method indicate that the expression contains only a null value and should not be included in the SQL statement.
     *
     * Will be checked for methods
     * - filterWhere()
     * - andFilterWhere()
     * - orFilterWhere()
     *
     * @return bool
     */
    public function isEmpty(): bool;
}
