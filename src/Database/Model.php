<?php

namespace Weebly\Mutate\Database;

use Weebly\Mutate\Database\Traits\HasMutators;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
    use HasMutators;

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->getAttributeFromArray($this->getKeyName());
    }

    /**
     * @param string $key
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function getAttribute($key)
    {
        if ($this->hasMutator($key)) {
            return $this->unserializeAttribute($key, $this->getAttributeFromArray($key));
        }

        return parent::getAttribute($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasMutator($key)) {
            // Set the serialized value on the parent attributes
            $this->attributes[$key] = $this->serializeAttribute($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($attributes as $attribute => $value) {
            if ($this->hasMutator($attribute)) {
                $attributes[$attribute] = $this->unserializeAttribute($attribute, $value);
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeyForSaveQuery()
    {
        return $this->getAttribute($this->getKeyName());
    }
}
