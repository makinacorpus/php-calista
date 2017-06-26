<?php

namespace MakinaCorpus\Dashboard\Util;

/**
 * A few helpers for managing data types
 */
final class TypeUtil
{
    /**
     * Normalize type, because sometime users don't get it right
     *
     * @param string $type
     *
     * @return string
     */
    static public function normalizeType($type)
    {
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

    /**
     * Get internal type of value
     *
     * @param string $value
     *
     * @return string
     */
    static public function getInternalType($value)
    {
        return self::normalizeType(gettype($value));
    }
}
