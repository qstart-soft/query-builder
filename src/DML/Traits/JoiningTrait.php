<?php

namespace Qstart\Db\QueryBuilder\DML\Traits;

use Qstart\Db\QueryBuilder\DML\Expression\ExprInterface;
use Qstart\Db\QueryBuilder\DML\Query\QueryInterface;

trait JoiningTrait
{
    use TableTrait;

    protected array $joiningList = [];

    /**
     * This is used to construct LEFT JOIN clauses.
     * @param string|array|ExprInterface $tables See README for table formats.
     * @param string|array|ExprInterface|QueryInterface $conditions See README for conditions format.
     * @return $this
     */
    public function leftJoin($table, $conditions)
    {
        $this->addJoining('LEFT JOIN', $table, $conditions);
        return $this;
    }

    /**
     * This is used to construct RIGHT JOIN clauses.
     * @param string|array|ExprInterface $tables See README for table formats.
     * @param string|array|ExprInterface|QueryInterface $conditions See README for conditions format.
     * @return $this
     */
    public function rightJoin($table, $conditions)
    {
        $this->addJoining('RIGHT JOIN', $table, $conditions);
        return $this;
    }

    /**
     * This is used to construct INNER JOIN clauses.
     * @param string|array|ExprInterface $tables See README for table formats.
     * @param string|array|ExprInterface|QueryInterface $conditions See README for conditions format.
     * @return $this
     */
    public function innerJoin($table, $conditions)
    {
        $this->addJoining('INNER JOIN', $table, $conditions);
        return $this;
    }

    /**
     * This is used to construct different JOIN clauses.
     * @param string|array|ExprInterface $tables See README for table formats.
     * @param string|array|ExprInterface|QueryInterface $conditions See README for conditions format.
     * @param string $type Join type
     * @return $this
     */
    public function join($table, $conditions, string $type = 'JOIN')
    {
        $this->addJoining($type, $table, $conditions);
        return $this;
    }

    /**
     * List of joins
     * @return array
     */
    public function getJoiningList(): array
    {
        return $this->joiningList;
    }

    protected function addJoining(string $type, $table, $conditions)
    {
        $this->joiningList[] = [
            'type' => $type,
            'table' => $this->normalizeTables($table),
            'conditions' => $conditions,
        ];
    }
}
