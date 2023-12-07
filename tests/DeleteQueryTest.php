<?php

namespace Qstart\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class DeleteQueryTest extends TestCase
{
    public function testDeleteBasic()
    {
        $query = Query::delete()->from('user');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), 'DELETE FROM user');

        $query = Query::delete()->from('user')->using('"table"');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), 'DELETE FROM user USING "table"');

        $query = Query::delete()->from('user')->setStartOfQuery('DELETE FROM ONLY')->setEndOfQuery('RETURNING id');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), 'DELETE FROM ONLY user RETURNING id');

        $query = Query::delete()
            ->from('user u')
            ->using('"table" t')
            ->where([
                'and',
                new Expr('t.id = u.id'),
                ['t.id' => 2]
            ]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "DELETE FROM user u USING \"table\" t WHERE (t.id = u.id) AND (t.id = :$v1)");
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        $query = Query::delete()
            ->from('user u')
            ->leftJoin('"table" t', 't.id = u.id')
            ->where(['t.id' => 2])->limit(1);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "DELETE FROM user u LEFT JOIN \"table\" t ON t.id = u.id WHERE t.id = :$v1 LIMIT 1");
        $this->assertSame($expr->getParams(), [$v1 => 2]);
    }
}
