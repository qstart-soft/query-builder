# qstart-soft/query-builder

Library for creating DML (Data Manipulation Language) SQL statements.

- Part 1. Basics
- Part 2. Table format
- Part 3. Conditions format
- Part 4. Select Sql Statement
- Part 5. INSERT Sql Statement
- Part 6. UPDATE Sql Statement
- Part 7. DELETE Sql Statement

## Part 1. Basics

**Entry point:** \
The entry point for creating SQL statements is the `Query::class` factory.

```php
use Qstart\Db\QueryBuilder\Query;

$select = Query::select();
$insert = Query::insert();
$update = Query::update();
$delete = Query::delete();
```

**Expressions:** \
Expressions are classes that inherit an \Qstart\Db\QueryBuilder\DML\Expression\ExprInterface interface. \
These classes allow you to create specific expressions for a SQL query.

The initial expression class allows you to pass an immutable expression that will be added to the query without modification

```php
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

$expr = new Expr('created_at > now()');

// This is the sql expression
$expression = $expr->getExpression();
// This is the binding params
$params = $expr->getParams();
```

Several other expressions:

```php
use Qstart\Db\QueryBuilder\DML\Expression\BetweenExpr;
use Qstart\Db\QueryBuilder\DML\Expression\CompareExpr;
use Qstart\Db\QueryBuilder\DML\Expression\InExpr;

$expr = new CompareExpr('!=', 'id', 20);
$expression = $expr->getExpression();
// id != :v1
$params = $expr->getParams();
// ['v1' => 20]

$expr = new BetweenExpr('id', 10, 20);
$expression = $expr->getExpression();
// id BETWEEN :v1 AND :v2
$params = $expr->getParams();
// ['v1' => 10, 'v2' => 20]

$expr = new InExpr('id', [10, 20], true);
$expression = $expr->getExpression();
// id NOT IN (:v3, :v4)
$params = $expr->getParams();
// ['v3' => 10, 'v4' => 20]
```

It is true that for different SQL dialects the same constructions may have different syntax. \
To do this, you can pass the dialect into the expression.

```php
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\Helper\DialectSQL;

$expr = new Expr('created_at > now()');

$expressionCh = $expr->getExpression(DialectSQL::CLICKHOUSE);
$expressionPg = $expr->getExpression(DialectSQL::POSTGRESQL);
$params = $expr->getParams();
```

**Creation:** \
Getting sql statement and binding parameters:

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::select();
// ...
$builder = $query->getQueryBuilder()
// If necessary, you can set the dialect for the query builder
$builder->setDialect(DialectSQL::POSTGRESQL);

$expr = $builder->build();
// Binding parameters
$params = $expr->getParams();
// Sql string
$sql = $expr->getExpression();
```

## Part 2. Table format

The table format is the same for all available methods

- join()
- innerJoin()
- leftJoin()
- rightJoin()
- SelectQuery::from()
- UpdateQuery::joinFrom()
- UpdateQuery::setTable()
- InsertQuery::into()
- DeleteQuery::from()

Available formats (Using the SelectQuery::from() method as an example):

```php

use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

$query = Query::select();

// Table name only
$query->from('user');
$query->from(['user']);
// Table name with alias
$query->from('user u');
$query->from(['u' => 'user']);

// You can also pass an expression or another query instead of the table name
$query->from(['u' => Query::select()->from('user')]);
// Result: SELECT * FROM (SELECT * FROM user) AS u
$query->from(['u' => new Expr("(SELECT * FROM user)")]);
// Result: SELECT * FROM (SELECT * FROM user) AS u
// Same thing without alias
$query->from(Query::select()->from('user'));
$query->from(new Expr("SELECT * FROM user"));

// You can also set several tables in any available format
$query->from(['u' => 'user', 's' => 'session']);
// Result: SELECT * FROM user AS u, session AS s
```

You can also change the alias of the first table for methods:

- SelectQuery::from()
- UpdateQuery::setTable()
- InsertQuery::into()
- DeleteQuery::from()

An example:

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::select()->from(['user'])->alias('u');
// Result: SELECT * FROM user AS u

$query = Query::select()->from(['u' => 'user'])->alias('t');
// Result: SELECT * FROM user AS t

$query = Query::select()->from(['u' => 'user', "s" => "session"])->alias('t');
// Result: SELECT * FROM user AS t, session AS s
```

---

## Part 3. Conditions format

Any condition can be passed in the following formats:

### 1. Array with equality conditions.

An array is a key-value pair. The key is the left expression. The value is ine of the options below

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

$conditions = ['user_id' => 10, 'session_id' => 101];
// user_id = 10 AND session_id = 101
$conditions = ['user_id' => [10, 20], 'session_id' => 101];
// user_id IN (10, 20) AND session_id = 101
$conditions = ['user_id' => Query::select()->select('id')->from('user'), 'session_id' => 101];
// user_id IN (SELECT id FROM user) AND session_id = 101
$conditions = ['user_id' => new Expr("LEAST(10, 20)"), 'session_id' => 101];
// user_id = LEAST(10, 20) AND session_id = 101

Query::select()->where($conditions);
```

### 2. Any Expression instance of ExprInterface

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\InExpr;

$conditions = new Expr('created_at >= now()');
// created_at >= now()
$conditions = new InExpr('id', [10, 20], true);
// id NOT IN (10, 20)

Query::select()->where($conditions);
```

### 3. String format

```php
use Qstart\Db\QueryBuilder\Query;

$conditions = 'created_at >= now()'
Query::select()->from('user')->where($conditions);
// SELECT * FROM user WHERE created_at >= now()

$conditions = [
    'and',
    'created_at >= now()',
    ['id' => 2]
]
Query::select()->from('user')->where($conditions);
// SELECT * FROM user WHERE (created_at >= now()) AND (id = 2)
```

### 4. Group using the "OR", "AND" "NOT" operators

Then it becomes necessary to combine conditions using the operators AND, OR, NOT. \
All of these combinations have the same format \[operator, condition, condition, ...]:

The first in the array must be the operator AND / OR / NOT. \
Next, separated by commas, are conditions in one of three formats (array, expression, string).
These conditions can also be in the format with the operator AND / OR / NOT

For example: `['AND', $condition1, $condition2, ['OR', $condition3, $condition4]]`

```php
use Qstart\Db\QueryBuilder\DML\Expression\Expr;
use Qstart\Db\QueryBuilder\DML\Expression\InExpr;

$conditions = ['and', ['user_id' => 10, 'session_id' => 101], new Expr("id = LEAST(10, 20)")];
// (user_id = 10 AND session_id = 101) AND (id = LEAST(10, 20))
$conditions = ['or', ['user_id' => 10, 'session_id' => 101], new Expr("id = LEAST(10, 20)")];
// (user_id = 10 AND session_id = 101) OR (id = LEAST(10, 20))
$conditions = ['not', ['user_id' => 10, 'session_id' => 101], new Expr("id = LEAST(10, 20)")];
// NOT ((user_id = 10 AND session_id = 101) AND (id = LEAST(10, 20)))

// Lets combine it
$conditions = [
    'and',
    ['or', ['id' => 2], ['id' => 3]],
    ['not', ['session_id' => 10]]
];
// ((id = 2) OR (id = 3)) AND (NOT (session_id = 10))
```

How to use this with a SELECT query, for example:

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::select()->where(['and', $condition1, $condition2]);
// Its equal with:
$query = Query::select()->where($condition1)->andWhere($condition2);

$query = Query::select()->where(['or', $condition1, $condition2]);
// Its equal with:
$query = Query::select()->where($condition1)->orWhere($condition2);
```

## Part 4. Select Sql Statement

### 1. Select

To construct a SELECT clause, you need to use methods

- Query::select()->select() to create SELECT clause
- Query::select()->addSelect() to add values to SELECT clause
- Query::select()->distinct(true) to add DISTINCT keyword

The method select() overwrites all previously added values!

The clause can be construct in different formats.

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

// 1. String format
Query::select()->select('id, name, surname');

// 2. Array alias-value format
Query::select()
    ->select([
        'id',
        'name' => "name || ' ' || surname",
        new Expr('created_at::DATE as date'),
        'cnt' => Query::select()->select('COUNT(*)')->from('user')
    ]);
// Result: SELECT id, name || ' ' || surname AS name, created_at::DATE as date, (SELECT COUNT(*) FROM user) AS cnt

// 3. Add values
Query::select()->select('id, name')->addSelect(new Expr('created_at::DATE as date'));
// Result: SELECT id, name, created_at::DATE as date

// 4. Reset values
Query::select()->select(null);

// 4. Distinct
Query::select()->select('id, name')->distinct(true);
// // Result: SELECT DISTINCT id, name
```

### 2. Where

To construct a WHERE clause, you need to use methods

- Query::select()->where() \
  to create WHERE clause
- Query::select()->andWhere() \
  Adding a condition using the AND operator to the current conditions. Identical - \['and', current conditions, new conditions]
- Query::select()->orWhere() \
  Adding a condition using the OR operator to the current conditions. Identical - \['or', current conditions, new conditions]

The method where() overwrites all previously added values!

All methods accept conditions in the format described above in 'Conditions format'.

```php
use Qstart\Db\QueryBuilder\Query;

Query::select()->where(['id' => 2])->andWhere(['user_id' => 3]);
```

You can also use methods that will remove all NULL values from the condition. For Expression instances the method ExprInterface::isEmpty() will be
called.

- Query::select()->filterWhere()
- Query::select()->andFilterWhere()
- Query::select()->orFilterWhere()

The method filterWhere() overwrites all previously added values!

All methods accept conditions in the format described above in 'Conditions format'.

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\CompareExpr;

Query::select()->filterWhere(['id' => null])->andFilterWhere(new CompareExpr('>=', 'id', null));
// Result will be without WHERE clause
```

### 3. Group By

To construct a GROUP BY clause, you need to use methods

- Query::select()->groupBy() to create GROUP BY clause
- Query::select()->addGroupBy() to add values to GROUP BY clause

The method groupBy() overwrites all previously added values!

The clause can be construct in different formats.

```php
use Qstart\Db\QueryBuilder\Query;

// 1. String format
Query::select()->from('user')->groupBy('id, name');
// SELECT * FROM user GROUP BY id, name

// 2. Array format
Query::select()->from('user')->groupBy(['id', 'name']);
// SELECT * FROM user GROUP BY id, name

// 3. Expression/Query format
Query::select()->from('user')->groupBy(new Expr('id, name'));
// SELECT * FROM user GROUP BY id, name

// 4. Add values
Query::select()->from('user')->groupBy('id, name')->addGroupBy(new Expr('created_at::DATE'));
// Result: SELECT * FROM user GROUP BY  id, name, created_at::DATE

// 5. Reset values
Query::select()->groupBy(null);
```

### 4. Order By

To construct a ORDER BY clause, you need to use methods

- Query::select()->orderBy() to create ORDER BY clause
- Query::select()->addOrderBy() to add values to ORDER BY clause

The method orderBy() overwrites all previously added values!

The clause can be construct in different formats.

```php
use Qstart\Db\QueryBuilder\Query;

// 1. String format
Query::select()->from('user')->orderBy('id, name');
// SELECT * FROM user ORDER BY id, name

// 2. Array format
Query::select()->from('user')->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC]);
// SELECT * FROM user ORDER BY id ASC, name DESC

// 3. Expression/Query format
Query::select()->from('user')->orderBy(new Expr('id ASC, name DESC'));
// SELECT * FROM user ORDER BY id ASC, name DESC

// 4. Mix format
Query::select()->from('user')->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC, new Expr('created_at::DATE DESC')]);
// SELECT * FROM user ORDER BY id ASC, name DESC, created_at::DATE DESC

// 5. Add values
Query::select()->from('user')->orderBy('id DESC')->addOrderBy(new Expr('created_at::DATE DESC'));
// Result: SELECT * FROM user ORDER BY id DESC, created_at::DATE DESC

// 6. Reset values
Query::select()->orderBy(null);
```

### 5. Having

To construct a HAVING clause, you need to use methods

- Query::select()->having()
- Query::select()->andHaving()
- Query::select()->orHaving()

The method having() overwrites all previously added values!

These methods work just look like 'WHERE' methods.

### 6. OFFSET

To construct a OFFSET clause, you need to use method

- Query::select()->offset()

Use null value to disable offset. \
The offset may be int|ExprInterface|SelectQuery|null

```php
use Qstart\Db\QueryBuilder\Query;

// 1. Integer
Query::select()->from('user')->offset(10);
// SELECT * FROM user OFFSET 10

// 2. Expression
Query::select()->from('user')->offset(new Expr("length('SPARK')"));
// SELECT * FROM user OFFSET length('SPARK')

// 2. Reset value
Query::select()->from('user')->offset(null);
```

### 7. LIMIT

To construct a LIMIT clause, you need to use method

- Query::select()->limit()

Use null value to disable limit. \
The offset may be int|ExprInterface|SelectQuery|null

```php
use Qstart\Db\QueryBuilder\Query;

// 1. Integer
Query::select()->from('user')->limit(10);
// SELECT * FROM user LIMIT 10

// 2. Expression
Query::select()->from('user')->limit(new Expr("length('SPARK')"));
// SELECT * FROM user LIMIT length('SPARK')

// 2. Reset value
Query::select()->from('user')->limit(null);
```

### 8. JOIN

To construct a different JOIN clauses, you need to use method

- Query::select()->join()
- Query::select()->leftJoin()
- Query::select()->rightJoin()
- Query::select()->innerJoin()

All these methods accept conditions in the format described above in 'Conditions format'. \
Also, all these methods accept table in the format described above in 'Table format'.

Example of usage:

```php
use Qstart\Db\QueryBuilder\Query;

Query::select()->from('user u')->leftJoin('session s', 'u.id = s.user_id');
// Result: SELECT * FROM user u LEFT JOIN session s ON u.id = s.user_id
```

### 9. UNION

To use union queries, you need to use method.

- Query::select()->union() to add union query
- Query::select()->deleteUnion() to delete all union queries

ORDER BY clause will be combined from all queries and added to the end of the union queries

The query may be string|ExprInterface|SelectQuery

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

$query = Query::select()
    ->from('table t')->where(['user_id' => 2])->orderBy('created_at')
    ->union(Query::select()->from('table2 t2')->where(['user_id' => 12])->orderBy('id'), true)
    ->union(Query::select()->from('table3 t3')->where(['user_id' => 22]))
    ->union(new Expr('SELECT * FROM table4 t4 WHERE user_id = :id', ['id' => 32]))
    ->union('SELECT * FROM table5 t5', true);

// Result:
<<<SQL
SELECT * FROM table t WHERE user_id = :v1
UNION ALL
SELECT * FROM table2 t2 WHERE user_id = :v2
UNION
SELECT * FROM table3 t3 WHERE user_id = :v3
UNION
SELECT * FROM table4 t4 WHERE user_id = :id
UNION ALL
SELECT * FROM table5 t5
ORDER BY created_at, id
SQL;

```

## Part 5. INSERT Sql Statement

Creating the INSERT INTO statement with format: \
INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);

To specify table name use:

- Query::insert()->into() \
  This method accept table in the format described above in 'Table format'.

To add group of values to a VALUES clause use methods:

- Query::insert()->addValues($data) \
  The data should be an array in the format \[column1 => value1, ...] or instance of QueryInterface
- Query::insert()->addMultipleValues($data) \
  The data should be an array of arrays in the format \[column1 => value1, ...]

To change start or end of statement use methods:

- Query::insert()->setStartOfQuery() \
  The expression `INSERT INTO` will be replaced with the passed expression
- Query::insert()->setEndOfQuery() \
  The expression will be added to the end of the query

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::insert()->into('user')->addValues(['name' => 'John', 'surname' => 'Jonson']);
// Result: INSERT INTO user (name, surname) VALUES (:v1, :v2)

$query->setStartOfQuery('INSERT IGNORE INTO')->setEndOfQuery('RETURNING id');
// Result: INSERT IGNORE INTO user (name, surname) VALUES (:v1, :v2) RETURNING id

$query = Query::insert()->into('user')->addMultipleValues([['name' => 'John', 'surname' => 'Jonson'], ['surname' => 'Nelson', 'name' => 'Mike']]);
// Result: INSERT INTO user (name, surname) VALUES (:v1, :v2), (:v4, :v3)
```

## Part 6. UPDATE Sql Statement

### 1. Table

To specify table name use:

- Query::update()->setTable() \
  This method accept table in the format described above in 'Table format'.

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::update()->setTable('user');
// Result: UPDATE user
```

### 2. SET

To construct a SET clause, you need to use methods

- Query::update()->set($attributes) \
  To create SET clause
- Query::update()->addSet($attributes) \
  To add attributes to SET clause

The method set() overwrites all previously added attributes!

Attributes can be passed in different formats. \
If we pass it with the key, we will try to add the value as a query parameter. \
If passed without a key, the value will be a string. \
The value can be passed as a string, an ExprInterface instance, or a QueryInterface instance.

```php
use Qstart\Db\QueryBuilder\Query;
use Qstart\Db\QueryBuilder\DML\Expression\Expr;

// 1. Format of attributes
$query = Query::update()
    ->setTable('"user"')
    ->set([
        'name' => 'John', // name=:v19,
        'age' => new Expr('18 + 10'), // age=18 + 10,
        'last_session_at' => Query::select()->from('session')->select('MAX(created_at)')->where(['user_id' => 123])
    ])
    ->addSet("status='active'") // status='active',
    ->addSet(new Expr('is_active = TRUE')); // is_active = TRUE,

// Result: UPDATE "user" SET name = :v1, age = 18 + 10, last_session_at = (SELECT MAX(created_at) FROM session WHERE user_id = :v2), status='active', is_active = TRUE

// 2. Reset SET clause
$query = Query::update()->setTable('"user"')->set(null);
```

### 3. Where

To construct a WHERE clause see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement

### 4. Join

To construct a different JOIN clauses see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement.

### 5. JOIN FROM

To construct a FROM clause, you need to use method:

- Query::update()->joinFrom()
  This method accept table in the format described above in 'Table format'.

Use null value to disable FROM clause

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::update()->setTable('user')->joinFrom('session');
// Result: UPDATE user FROM session
```

### 6. Limit

To construct a LIMIT clause see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement

### 7. Start and end clauses

To change start or end of statement use methods:

- Query::update()->setStartOfQuery() \
  The expression `UPDATE` will be replaced with the passed expression
- Query::update()->setEndOfQuery() \
  The expression will be added to the end of the query

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::update()
    ->setTable('user')
    ->setStartOfQuery('UPDATE ONLY')
    ->setEndOfQuery('RETURNING id');
// Result: UPDATE ONLY user RETURNING id
```

## Part 7. DELETE Sql Statement

### 1. From

To specify table name use:

- Query::delete()->from() \
  This method accept table in the format described above in 'Table format'.

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::delete()->from('user');
// Result: DELETE FROM user
```

### 2. Where

To construct a WHERE clause see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement

### 3. Join

To construct a different JOIN clauses see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement.

### 4. Using

To construct a USING clause, you need to use method:

- Query::delete()->using()
  This method accept table in the format described above in 'Table format'.

Use null value to disable USING clause

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::delete()->from('user')->using('session');
// Result: DELETE FROM user USING session
```

### 5. Limit

To construct a LIMIT clause see the description in "Part 3. Select Sql Statement".
Format and methods will be completely identical with Select Sql Statement

### 6. Start and end clauses

To change start or end of statement use methods:

- Query::delete()->setStartOfQuery() \
  The expression `DELETE FROM` will be replaced with the passed expression
- Query::delete()->setEndOfQuery() \
  The expression will be added to the end of the query

```php
use Qstart\Db\QueryBuilder\Query;

$query = Query::delete()
    ->from('user')
    ->setStartOfQuery('DELETE FROM ONLY')
    ->setEndOfQuery('RETURNING id');
// Result: DELETE FROM ONLY user RETURNING id
```

