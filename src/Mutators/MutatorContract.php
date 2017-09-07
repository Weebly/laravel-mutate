<?php

namespace Weebly\Mutate\Mutators;

interface MutatorContract
{
    /**
     * @param mixed $value
     */
    public function serializeAttribute($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function unserializeAttribute($value);
}
