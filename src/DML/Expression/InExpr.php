<?php

namespace Qstart\Db\QueryBuilder\DML\Expression;

use Qstart\Db\QueryBuilder\DML\Builder\ValueBuilder;

/**
 * Class for creating an expressions `IN` and `NOT IN`
 */
class InExpr implements ExprInterface
{
    protected $expression;
    protected $params;
    protected bool $empty = false;

    /**
     * Main examples of usage:
     *
     * new InExpr('id', [10, 20]);
     *
     * new InExpr('id', Query::select()->select('id')->from('user'));
     *
     * new InExpr(['id', 'name'], [['id' => 10, 'name' => 'John'], ['id' => 20, 'name' => 'Mike']]);
     *
     * @param $leftOperand
     * @param $rightOperand
     * @param bool $not
     */
    public function __construct($leftOperand, $rightOperand, bool $not = false)
    {
        [$leftOperand, $rightOperand] = $this->normalizedValue($leftOperand, $rightOperand);

        if ($rightOperand === null) {
            $this->empty = true;
        }

        $leftExpr = (new ValueBuilder($leftOperand, false))->build();
        $rightExpr = (new ValueBuilder($rightOperand, true))->build();

        $this->expression = sprintf(
            '%s %s %s',
            $leftExpr->getExpression(),
            $not === true ? 'NOT IN' : 'IN',
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

    protected function normalizedValue($leftOperand, $rightOperand)
    {
        if (
            is_array($leftOperand)
            && !array_filter($leftOperand, fn($v) => !is_string($v))
            && !array_filter($rightOperand, fn($v) => !is_array($v))
        ) {
            $sortedRightOperand = [];
            $sortedLeftOperand = array_values($leftOperand);
            foreach ($rightOperand as $values) {
                $filteredValues = array_filter($values, fn($v) => !is_array($v));
                $filteredValues = array_filter($filteredValues, fn($k) => in_array($k, $sortedLeftOperand), ARRAY_FILTER_USE_KEY);
                if ($values && count($values) === count($filteredValues)) {
                    $sortedValues = [];
                    foreach ($sortedLeftOperand as $key) {
                        $sortedValues[] = $values[$key];
                    }
                } else {
                    $sortedRightOperand = null;
                    break;
                }

                $sortedRightOperand[] = $sortedValues;
            }
            $sortedRightOperand && $rightOperand = $sortedRightOperand;
        }

        return [$leftOperand, $rightOperand];
    }
}
