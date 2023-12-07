<?php

namespace Qstart\Db\Tests;

use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Helper\BindingParamName;
use Qstart\Db\QueryBuilder\Query;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testTableFormat()
    {
        $query = Query::select()->from('user');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from('"user"');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM \"user\"");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['user']);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['u' => 'user']);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user AS u");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['u' => 'user', "s" => "session"]);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user AS u, session AS s");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['u' => Query::select()->from('user')->where(['id' => 123])]);
        $expr = $query->getQueryBuilder()->build();
        $v1 = BindingParamName::getName(BindingParamName::getN());
        $this->assertSame($expr->getExpression(), "SELECT * FROM (SELECT * FROM user WHERE id = :{$v1}) AS u");
        $this->assertSame($expr->getParams(), [$v1 => 123]);

        $query = Query::select()->from(['u' => new Expr("(SELECT * FROM user WHERE id = :id)", ['id' => 123])]);
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM (SELECT * FROM user WHERE id = :id) AS u");
        $this->assertSame($expr->getParams(), ['id' => 123]);

        // Alias

        $query = Query::select()->from(['user'])->alias('u');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user AS u");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['u' => 'user'])->alias('t');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user AS t");
        $this->assertSame($expr->getParams(), []);

        $query = Query::select()->from(['u' => 'user', "s" => "session"])->alias('t');
        $expr = $query->getQueryBuilder()->build();
        $this->assertSame($expr->getExpression(), "SELECT * FROM user AS t, session AS s");
        $this->assertSame($expr->getParams(), []);
    }
}
