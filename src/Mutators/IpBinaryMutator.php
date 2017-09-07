<?php

namespace Weebly\Mutate\Mutators;

class IpBinaryMutator implements MutatorContract
{
    /**
     * @param mixed $value
     * @return string
     */
    public function serializeAttribute($value)
    {
        return inet_pton($value);
    }

    /**
     * @param mixed $value
     * @return bool|string
     */
    public function unserializeAttribute($value)
    {
        return inet_ntop($value);
    }
}
