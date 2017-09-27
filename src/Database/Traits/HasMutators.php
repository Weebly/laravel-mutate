<?php

namespace Weebly\Mutate\Database\Traits;

trait HasMutators
{
    /**
     * @var array
     */
    protected $mutate = [
        // 'attribute' => 'mutator_name'
    ];

    /**
     * @var array
     */
    protected $mutatedCache = [];

    protected function mutatorAcceptsAttribute($mutator) {
        $mutatorClass = get_class($mutator);
        $serialize = new \ReflectionMethod($mutatorClass, 'serializeAttribute');
        $unserialize = new \ReflectionMethod($mutatorClass, 'unserializeAttribute');
        $doesAcceptAttribute = $serialize->getNumberOfParameters() == 2 && $unserialize->getNumberOfParameters() == 2;
        return $doesAcceptAttribute;
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function serializeAttribute($attribute, $value)
    {
        // Mutate the attribute if a mutator is defined
        if ($this->hasMutator($attribute)) {
            $mutator = app('mutator')
                ->get($this->getMutator($attribute));

            if ($this->mutatorAcceptsAttribute($mutator)) {
                $mutated = $mutator->serializeAttribute($value, $attribute);
            } else {
                $mutated = $mutator->serializeAttribute($value);
            }

            // Keep a cached copy of the unserialized value
            $this->mutatedCache[$attribute] = $value;

            return $mutated;
        }

        return $value;
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     * @return mixed
     */
    public function unserializeAttribute($attribute, $value)
    {
        // Mutate the attribute if a mutator is defined
        if ($this->hasMutator($attribute)) {
            if (array_key_exists($attribute, $this->mutatedCache)) {
                return $this->mutatedCache[$attribute];
            }

            $mutator = app('mutator')
                ->get($this->getMutator($attribute));

            if ($this->mutatorAcceptsAttribute($mutator)) {
                $mutated = $mutator->unserializeAttribute($value, $attribute);
            } else {
                $mutated = $mutator->unserializeAttribute($value);
            }
            $this->mutatedCache[$attribute] = $mutated;

            return $mutated;
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getMutators()
    {
        return $this->mutate;
    }

    /**
     * @param string $attribute
     * @return mixed|null
     */
    public function getMutator($attribute)
    {
        if ($this->hasMutator($attribute)) {
            return $this->mutate[$attribute];
        }
    }

    /**
     * @param mixed $attribute
     * @return bool
     */
    public function hasMutator($attribute)
    {
        return array_key_exists($attribute, $this->mutate);
    }
}
