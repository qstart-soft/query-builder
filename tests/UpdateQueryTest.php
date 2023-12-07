<?php

namespace Qstart\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class UpdateQueryTest extends TestCase
{
    public function testUpdateQuery()
    {
        $query = Query::update()->setTable('user');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "UPDATE user");

        $query = Query::update()
            ->setTable('"user"')
            ->set([
                'name' => 'John',
                'age' => new Expr('18 + 10'),
                'last_session_at' => Query::select()->from('session')->select('MAX(created_at)')->where(['user_id' => 123])
            ])
            ->addSet("status='active'");
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "UPDATE \"user\" SET name = :$v1, age = 18 + 10, last_session_at = (SELECT MAX(created_at) FROM session WHERE user_id = :$v2), status='active'");
        $this->assertSame($expr->getParams(), [$v1 => 'John', $v2 => 123]);

        $query = Query::update()->setTable('user')->setStartOfQuery('UPDATE ONLY')->setEndOfQuery('RETURNING id');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), 'UPDATE ONLY user RETURNING id');

        $query = Query::update()
            ->setTable('user u')
            ->set(new Expr("status = 'active'"))
            ->joinFrom('"table" t')
            ->where([
                'and',
                new Expr('t.id = u.id'),
                ['t.id' => 2]
            ]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "UPDATE user u SET status = 'active' FROM \"table\" t WHERE (t.id = u.id) AND (t.id = :$v1)");
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        $query = Query::update()
            ->setTable('user u')
            ->set(new Expr("status = 'active'"))
            ->leftJoin('"table" t', 't.id = u.id')
            ->where(['t.id' => 2])->limit(1);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "UPDATE user u SET status = 'active' LEFT JOIN \"table\" t ON t.id = u.id WHERE t.id = :$v1 LIMIT 1");
        $this->assertSame($expr->getParams(), [$v1 => 2]);
    }
}
