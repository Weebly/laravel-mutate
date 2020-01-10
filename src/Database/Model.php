<?php

namespace Weebly\Mutate\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Weebly\Mutate\Database\Eloquent\Relations\BelongsToMany;
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
     * {@inheritdoc}
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasMutator($key) && ! is_null($value)) {
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
     * Define a many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
                                  $parentKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToMany(
            $instance->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
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
