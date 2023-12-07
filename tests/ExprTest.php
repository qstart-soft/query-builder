<?php

namespace Qstart\Db\Tests;

use Qstart\Db\QueryBuilder\DML\Expression\BetweenExpr;
use Qstart\Db\QueryBuilder\DML\Expression\CompareExpr;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\InExpr;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class ExprTest extends \PHPUnit\Framework\TestCase
{
    public function testBetweenExpr()
    {
        $values = [10, 20];
        $expr = new BetweenExpr('id', $values[0], $values[1]);
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "id BETWEEN :{$v1} AND :{$v2}");
        $this->assertSame($expr->getParams(), [$v1 => $values[0], $v2 => $values[1]]);

        $expr = new BetweenExpr('created_at::DATE', $values[0], $values[1], true);
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "created_at::DATE NOT BETWEEN :{$v1} AND :{$v2}");
        $this->assertSame($expr->getParams(), [$v1 => $values[0], $v2 => $values[1]]);

        $values = [new Expr("'2023-01-01'::DATE"), new Expr("'2023-02-01'::DATE")];
        $expr = new BetweenExpr('id', $values[0], $values[1]);
        $this->assertSame($expr->getExpression(), "id BETWEEN '2023-01-01'::DATE AND '2023-02-01'::DATE");
        $this->assertSame($expr->getParams(), []);
    }

    public function testInExpr()
    {
        $expr = new InExpr('id', [10, 20]);
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "id IN (:{$v1}, :{$v2})");
        $this->assertSame($expr->getParams(), [$v1 => 10, $v2 => 20]);

        $expr = new InExpr('id', Query::select()->select('id')->from('user'));
        $this->assertSame($expr->getExpression(), "id IN (SELECT id FROM user)");
        $this->assertSame($expr->getParams(), []);

        $expr = new InExpr(['id', 'name'], [['id' => 10, 'name' => 'John'], ['id' => 20, 'name' => 'Mike']]);
        $v1 = BindingParamName::getName(BindingParamName::getN() - 3);
        $v2 = BindingParamName::getName(BindingParamName::getN() - 2);
        $v3 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v4 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "(id, name) IN ((:{$v1}, :{$v2}), (:{$v3}, :{$v4}))");
        $this->assertSame($expr->getParams(), [$v1 => 10, $v2 => 'John', $v3 => 20, $v4 => 'Mike']);
    }

    public function testCompareExpr()
    {
        $expr = new CompareExpr('!=', 'id', 2);
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "id != :{$v1}");
        $this->assertSame($expr->getParams(), [$v1 => 2]);
    }
}
