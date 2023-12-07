<?php

namespace Qstart\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qstart\Db\QueryBuilder\DML\Expression\CompareExpr;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class SelectQueryTest extends TestCase
{
    public function testSelectClause()
    {
        $query = Query::select()->select('id, name, surname')->distinct(false);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT id, name, surname");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->select('id, name, surname')->distinct(true);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT DISTINCT id, name, surname");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->select([
            'id',
            'name' => "name || ' ' || surname",
            new Expr('created_at::DATE as date'),
            'cnt' => Query::select()->select('COUNT(*)')->from('user')
        ]);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame(
            $expr->getExpression(),
            "SELECT id, name || ' ' || surname AS name, created_at::DATE as date, (SELECT COUNT(*) FROM user) AS cnt",
        );
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->select('id, name')->addSelect(new Expr('created_at::DATE as date'));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT id, name, created_at::DATE as date");
        $this->assertSame($expr->getParams(), []);
    }

    public function testGroupByClause()
    {
        $query = Query::select()->from('user')->groupBy('id, name');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user GROUP BY id, name");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->groupBy(['id', 'name']);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user GROUP BY id, name");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->groupBy(new Expr('id, name'));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user GROUP BY id, name");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->groupBy(new Expr('id, name'))->addGroupBy(new Expr('created_at::DATE'));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user GROUP BY id, name, created_at::DATE");
        $this->assertSame($expr->getParams(), []);
    }

    public function testOrderByClause()
    {
        $query = Query::select()->from('user')->orderBy('id, name');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user ORDER BY id, name");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC, new Expr('created_at::DATE DESC')]);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user ORDER BY id ASC, name DESC, created_at::DATE DESC");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->orderBy(new Expr('id ASC, name DESC'));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user ORDER BY id ASC, name DESC");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->orderBy('id DESC')->addOrderBy(new Expr('created_at::DATE DESC'));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user ORDER BY id DESC, created_at::DATE DESC");
        $this->assertSame($expr->getParams(), []);
    }

    public function testHavingClause()
    {
        // 1. Array with equality conditions

        $query = Query::select()->from(['user'])->having([
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
            "SELECT * FROM user HAVING id = :$v1 AND session_id = :$v2 AND user_id IN (:$v3, :$v4) AND client_id IN (SELECT id FROM user u) AND created_at = now()"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 10, $v3 => 12, $v4 => 13]);

        // 2. Any Expression

        $query = Query::select()->from(['user'])
            ->having(new Expr('created_at >= now()'))
            ->andHaving(new CompareExpr('>', 'id', 2))
            ->orHaving(['id' => 3]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user HAVING ((created_at >= now()) AND (id > :$v1)) OR (id = :$v2)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 3]);

        // AND
        $query = Query::select()->from(['user'])
            ->having(['and', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user HAVING (created_at >= now()) AND (id > :$v1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // OR
        $query = Query::select()->from(['user'])
            ->having(['or', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user HAVING (created_at >= now()) OR (id > :$v1)"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // NOT
        $query = Query::select()->from(['user'])
            ->having(['not', new Expr('created_at >= now()'), new CompareExpr('>', 'id', 2)]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user HAVING NOT ((created_at >= now()) AND (id > :$v1))"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2]);

        // Common
        $query = Query::select()->from(['user'])
            ->having(['and', new Expr('created_at >= now()'), ['or', new CompareExpr('>', 'id', 2), ['not', ['id' => 3]]]]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v2 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM user HAVING (created_at >= now()) AND ((id > :$v1) OR (NOT (id = :$v2)))"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 3]);
    }

    public function testOffsetClause()
    {
        $query = Query::select()->from('user')->offset(10);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user OFFSET 10");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->offset(new Expr("length('SPARK')"));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user OFFSET length('SPARK')");
        $this->assertSame($expr->getParams(), []);
    }

    public function testLimitClause()
    {
        $query = Query::select()->from('user')->limit(10);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user LIMIT 10");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user')->limit(new Expr("length('SPARK')"));
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user LIMIT length('SPARK')");
        $this->assertSame($expr->getParams(), []);
    }

    public function testJoins()
    {
        $query = Query::select()->from('user u')->leftJoin('session s', 'u.id = s.user_id');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user u LEFT JOIN session s ON u.id = s.user_id");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user u')->rightJoin('session s', 'u.id = s.user_id');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user u RIGHT JOIN session s ON u.id = s.user_id");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user u')->innerJoin('session s', 'u.id = s.user_id');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user u INNER JOIN session s ON u.id = s.user_id");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('user u')->join('session s', 'u.id = s.user_id', 'CROSS OUTER JOIN');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user u CROSS OUTER JOIN session s ON u.id = s.user_id");
        $this->assertSame($expr->getParams(), []);
    }

    public function testUnions()
    {
        $query = Query::select()->from('table t')->where(['user_id' => 2])->orderBy('created_at')
            ->union(Query::select()->from('table2 t2')->where(['user_id' => 12])->orderBy('id'), true)
            ->union(Query::select()->from('table3 t3')->where(['user_id' => 22]))
            ->union(new Expr('SELECT * FROM table4 t4 WHERE user_id = :id', ['id' => 32]))
            ->union('SELECT * FROM table5 t5', true);

        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN() - 2);
        $v2 = BindingParamName::getName(BindingParamName::getN() - 1);
        $v3 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame(
            $expr->getExpression(),
            "SELECT * FROM table t WHERE user_id = :$v1 UNION ALL SELECT * FROM table2 t2 WHERE user_id = :$v2 UNION SELECT * FROM table3 t3 WHERE user_id = :$v3 UNION SELECT * FROM table4 t4 WHERE user_id = :id UNION ALL SELECT * FROM table5 t5 ORDER BY created_at, id"
        );
        $this->assertSame($expr->getParams(), [$v1 => 2, $v2 => 12, $v3 => 22, 'id' => 32]);
    }
}
