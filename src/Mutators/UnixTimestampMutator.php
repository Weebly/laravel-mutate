<?php

namespace Weebly\Mutate\Mutators;

use Carbon\Carbon;
use Weebly\Mutate\Exceptions\MutateException;

/**
 * Presents an attribute as Carbon date object (http://carbon.nesbot.com/)
 * Stores the attribute as unix epoch timestamp.
 */
class UnixTimestampMutator implements MutatorContract
{
    /**
     * {@inheritDoc}
     */
    public function serializeAttribute($value)
    {
        if (!$value instanceof Carbon) {
            throw new MutateException(__METHOD__." expects a Carbon\Carbon value. Received: ". print_r($value, true));
        }
        return $value->timestamp;;
    }

    /**
     * {@inheritDoc}
     */
    public function unserializeAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return Carbon::createFromTimestamp($value);
    }
}
