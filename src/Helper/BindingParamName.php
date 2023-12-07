<?php

namespace Qstart\Db\QueryBuilder\Helper;

/**
 * Class for getting the parameter name for a SQL statement
 */
class BindingParamName
{
    protected static int $n = 0;

    /**
     * To correctly process bound query parameters, we statically increment the parameter name.
     * @return string
     */
    public static function getNextName(): string
    {
        self::$n++;

        $name = "v" . self::$n;

        return $name;
    }

    /**
     * Last parameter name issued
     * @return string
     */
    public static function getCurrentName(): string
    {
        $name = "v" . self::$n;

        return $name;
    }


    /**
     * Specific parameter name issued
     * @param int $n parameter number
     * @return string
     */
    public static function getName(int $n): string
    {
        $name = "v" . $n;

        return $name;
    }

    /**
     * Number of parameters issued
     * @return int
     */
    public static function getN(): int
    {
        return self::$n;
    }
}
