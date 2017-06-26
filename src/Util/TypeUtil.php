<?php

namespace MakinaCorpus\Dashboard\Util;

/**
 * A few helpers for managing data types
 */
final class TypeUtil
{
    /**
     * Get internal type of value
     *
     * @param string $value
     *
     * @return string
     */
    static public function getInternalType($value)
    {
        $type = gettype($value);

        switch ($type) {

            case 'integer':
                return 'int';

            case 'boolean':
                return 'bool';

            case 'double':
                return 'float';

            default:
                return $type;
        }
    }
}
