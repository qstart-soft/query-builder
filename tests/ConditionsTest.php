<?php

namespace Qstart\Db\Tests;

use Qstart\Db\QueryBuilder\DML\Expression\CompareExpr;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Exception\QueryBuilderException;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class ConditionsTest extends \PHPUnit\Framework\TestCase
{
    public function testConditionsFormat()
    {
        // 1. Array with equality conditions

        $query = Query::select()->from(['user'])->where([
            'id' => 2,
            'session_id' => 10,
            'user_id' => [12, 13],
            'client_id' => Query::select()->from('user u')->select('id'),
            'created_at' => new Expr('now()')
        ]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 3);
        $v2 = BindingParamName::getName(BindingParamName::getN() - 2);
        $v3 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v4 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE id = :$v1 AND session_id = :$v2 AND user_id IN (:$v3, :$v4) AND client_id IN (SELECT id FROM user u) AND created_at = now()"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 10, $v3 => 12, $v4 => 13]);

        // Empty IN condition
        $query = Query::select()->from(['user'])->where([
            'id' => 2,
            'session_id' => 10,
            'user_id' => [],
        ]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE id = :$v1 AND session_id = :$v2 AND (0=1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 10]);

        // 2. Any Expression

        $query = Query::select()->from(['user'])
            ->where(new Expr('created_at >= now()'))
            ->andWhere(new CompareExpr('>', 'id', 2));
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE (created_at >= now()) AND (id > :$v1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        $query = Query::select()->from(['user'])->where('created_at >= now()');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user WHERE created_at >= now()");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['user'])->where(['or', 'created_at >= now()', 'id = 2']);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user WHERE (created_at >= now()) OR (id = 2)");
        $this->assertSame($expr->getParams(), []);

        // AND
        $query = Query::select()->from(['user'])
            ->where(['and', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE (created_at >= now()) AND (id > :$v1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // OR
        $query = Query::select()->from(['user'])
            ->where(['or', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE (created_at >= now()) OR (id > :$v1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // NOT
        $query = Query::select()->from(['user'])
            ->where(['not', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE NOT ((created_at >= now()) AND (id > :$v1))"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // Common
        $query = Query::select()->from(['user'])
            ->where(['and', new Expr('created_at >= now()'), ['or', new CompareExpr('>', 'id', 2), ['not', ['id' => 3]]]]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user WHERE (created_at >= now()) AND ((id > :$v1) OR (NOT (id = :$v2)))"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 3]);

        // Exceptions

        $this->expectException(QueryBuilderException::class);
        $query = Query::select()->from(['user'])->where(['id = 2']);
        $expr = $query->getQueryBuilder()->build();
    }
}
