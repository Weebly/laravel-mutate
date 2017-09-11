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
     * {@inheritdoc}
     */
    public function serializeAttribute($value)
    {
        if (! ctype_xdigit($value) || strlen($value) % 2 !== 0) {
            throw new MutateException(__METHOD__.' expects the value to be serialized to be a hexadecimal string.');
        }

        return hex2bin($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserializeAttribute($value)
    {
        if (! $value) {
            return;
        }

        return bin2hex($value);
    }
}
