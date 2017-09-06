<?php

namespace Weebly\Mutate\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weebly\Mutate\Database\Traits\HasMutators;

abstract class Model extends Eloquent
{
    use HasMutators;

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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    /**
     * {@inheritDoc}
     */
    protected function getKeyForSaveQuery()
    {
        return $this->getAttribute($this->getKeyName());
    }
}
