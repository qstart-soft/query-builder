<?php

namespace Qstart\Db\QueryBuilder;

use Qstart\Db\QueryBuilder\DML\Query\DeleteQuery;
use Qstart\Db\QueryBuilder\DML\Query\InsertQuery;
use Qstart\Db\QueryBuilder\DML\Query\SelectQuery;
use Qstart\Db\QueryBuilder\DML\Query\UpdateQuery;

/**
 * Base class for creating SQL statements
 */
class Query
{
    /**
     * Instance to create an SELECT statement
     * @return SelectQuery
     */
    public static function select(): SelectQuery
    {
        return new SelectQuery();
    }

    /**
     * Instance to create an DELETE statement
     * @return DeleteQuery
     */
    public static function delete(): DeleteQuery
    {
        return new DeleteQuery();
    }

    /**
     * Instance to create an UPDATE statement
     * @return UpdateQuery
     */
    public static function update(): UpdateQuery
    {
        return new UpdateQuery();
    }

    /**
     * Instance to create an INSERT statement
     * @return InsertQuery
     */
    public static function insert(): InsertQuery
    {
        return new InsertQuery();
    }
}
