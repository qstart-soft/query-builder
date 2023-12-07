<?php

namespace Qstart\Db\QueryBuilder\DML\Builder;

use Qstart\Db\QueryBuilder\DML\Expression\Expr;

interface BuilderInterface
{
    /**
     * Build the query and return the expression
     * @return Expr
     */
    public function build(): Expr;

    /**
     * Set the dialect for the builder. The dialect will be passed on to all nested expressions and queries
     * @param $dialect
     * @return BuilderInterface
     */
    public function setDialect($dialect): BuilderInterface;

    /**
     * Get the dialect for the builder.
     * @return mixed
     */
    public function getDialect();
}
