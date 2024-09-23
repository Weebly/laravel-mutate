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

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function serializeAttribute($attribute, $value)
    {
        // Mutate the attribute if a mutator is defined
        if ($this->hasMutator($attribute)) {
            $mutated = app('mutator')
                ->get($this->getMutator($attribute))
                ->serializeAttribute($value);

            // Keep a cached copy of the unserialized value
            $this->mutatedCache[$attribute] = $value;

            return $mutated;
        }

        return $value;
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  bool  $force
     * @return mixed
     */
    public function unserializeAttribute($attribute, $value, $force = false)
    {
        // Mutate the attribute if a mutator is defined
        if ($this->hasMutator($attribute)) {
            if ($force === false && array_key_exists($attribute, $this->mutatedCache)) {
                return $this->mutatedCache[$attribute];
            }

            $mutated = app('mutator')
                ->get($this->getMutator($attribute))
                ->unserializeAttribute($value);

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
     * @param  string  $attribute
     * @return mixed|null
     */
    public function getMutator($attribute)
    {
        if ($this->hasMutator($attribute)) {
            return $this->mutate[$attribute];
        }
    }

    /**
     * @param  mixed  $attribute
     * @return bool
     */
    public function hasMutator($attribute)
    {
        return array_key_exists($attribute, $this->mutate);
    }

    /**
     * @return void
     */
    protected function clearMutatorCache()
    {
        $this->mutatedCache = [];
    }
}
