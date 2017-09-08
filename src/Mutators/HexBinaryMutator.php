<?php

namespace Weebly\Mutate\Mutators;

use Weebly\Mutate\Exceptions\MutateException;

/**
 * Presents an attribute as a hexadecimal string.
 * Stores the attribute as a byte array.
 */
class HexBinaryMutator implements MutatorContract
{
    /**
     * {@inheritDoc}
     */
    public function serializeAttribute($value)
    {
        if (!\ctype_xdigit($value)) {

            throw new MutateException(__METHOD__." expects the value to be serialized to be a hexadecimal string.");
        }
        return hex2bin($value);
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return bin2hex($value);
    }
}
