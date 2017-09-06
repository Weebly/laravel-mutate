<?php

namespace Weebly\Mutate\Mutators;

abstract class AbstractMutator
{
    /**
     * @param mixed $value
     */
    abstract public function serializeAttribute($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function unserializeAttribute($value);
}
