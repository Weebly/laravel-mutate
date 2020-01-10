<?php

namespace Weebly\Mutate;

use ArrayAccess;
use Weebly\Mutate\Exceptions\MutatorNotFoundException;
use Weebly\Mutate\Mutators\MutatorContract;

class MutatorProvider implements ArrayAccess
{
    /**
     * @var array
     */
    protected $mutators = [];

    /**
     * @param string $mutator
     * @return \Weebly\Mutate\Mutators\MutatorContract
     * @throws \Weebly\Mutate\Exceptions\MutatorNotFoundException
     */
    public function get($mutator)
    {
        if ($this->exists($mutator)) {
            $mutator = $this->mutators[$mutator];
            $mutator = (! $mutator instanceof MutatorContract) ? app($mutator) : $mutator;

            return $mutator;
        }

        throw new MutatorNotFoundException("No mutator handler registered for `{$mutator}`");
    }

    /**
     * @param string $name
     * @param mixed  $mutator
     * @return $this
     */
    public function set($name, $mutator)
    {
        $this->mutators[$name] = $mutator;

        return $this;
    }

    /**
     * @param string $mutator
     * @return bool
     */
    public function exists($mutator)
    {
        return array_key_exists($mutator, $this->mutators);
    }

    /**
     * @param array $mutators
     */
    public function registerMutators(array $mutators)
    {
        // Loop over every transform and register it
        foreach ($mutators as $name => $mutator) {
            $this->set($name, $mutator);
        }
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * @param string $offset
     * @return \Weebly\Mutate\Mutators\MutatorContract
     * @throws \Weebly\Mutate\Exceptions\MutatorNotFoundException
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed  $value
     * @return \Weebly\Mutate\MutatorProvider
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->mutators[$offset]);
    }

    /**
     * @param string $name
     * @return \Weebly\Mutate\Mutators\MutatorContract
     * @throws \Weebly\Mutate\Exceptions\MutatorNotFoundException
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
