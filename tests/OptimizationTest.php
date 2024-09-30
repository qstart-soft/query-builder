<?php

namespace Qstart\Db\Tests;

use PHPUnit\Framework\TestCase;
use Qstart\Db\QueryBuilder\Query;

/**
 * Testing optimization and memory
 */
class OptimizationTest extends TestCase
{
    public function testBatchInsert()
    {
        $start = microtime(true);
        $rows = [];
        foreach (range(1, 10000) as $item) {
            $rows[] = [
                'id' => $item,
                'val1' => 'val1_' . $item,
                'val2' => 'val2_' . $item,
                'val3' => 'val3_' . $item,
                'val4' => 'val4_' . $item,
                'val5' => 'val5_' . $item,
                'val6' => 'val6_' . $item,
                'val7' => 'val7_' . $item,
                'val8' => 'val8_' . $item,
            ];
        }

        $query = Query::insert()->into('table');
        $query->addMultipleValues($rows);

        $sql = $query->getQueryBuilder()->build()->getExpression();
        $params = $query->getQueryBuilder()->build()->getParams();

        $diff = round(microtime(true) - $start, 2);
        $this->assertTrue($diff < 5);
    }
}
