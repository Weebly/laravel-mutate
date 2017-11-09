<?php

namespace Weebly\Mutate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Weebly\Mutate\Exceptions\MutateException;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

class BelongsToMany extends EloquentBelongsToMany
{
    /**
     * Local cache of mutated attributes
     * 
     * @var array
     */
    protected $mutated_values = [];

    /**
     * If parent model uses mutator for its key, serialize the attribute.
     *
     * @return mixed
     */
    protected function getParentKeyValue()
    {
        $value = $this->parent->{$this->parentKey};
        if ($this->parent->hasMutator($this->parentKey)) {
            $value = $this->parent->serializeAttribute($this->parentKey, $value);
        }

        return $value;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->getParentKeyValue()
        );

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        if ($this->parent->hasMutator($this->parentKey) === false) {
            parent::addEagerConstraints($models);
        }

        $this->query->whereIn($this->getQualifiedForeignPivotKeyName(), $this->parseIds($this->getKeys($models, $this->parentKey)));
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        $dictionary = [];

        foreach ($results as $result) {
            $key = $result->{$this->accessor}->{$this->foreignPivotKey};

            // If the pivots parent serializes its key, than the pivot result will also be serialized
            // This means dictionary lookups will happen with a unserialized key (hex instead of binary)
            // We need to build the dictionary using unserialized keys so lookups will succeed
            if ($result->{$this->accessor}->pivotParent->hasMutator($this->parentKey)) {
                $mutator = $result->{$this->accessor}->pivotParent->getMutator($this->parentKey);
                $key = app('mutator')
                    ->get($mutator)
                    ->unserializeAttribute($key);
            }
            $dictionary[$key][] = $result;
        }

        return $dictionary;
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        $record[$this->relatedPivotKey] = $id;

        $record[$this->foreignPivotKey] = $this->getParentKeyValue();

        // If the record needs to have creation and update timestamps, we will make
        // them by calling the parent model's "freshTimestamp" method which will
        // provide us with a fresh timestamp in this model's preferred format.
        if ($timed) {
            $record = $this->addTimestampsToAttachment($record);
        }

        return $record;
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
    {
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $arguments) {
            call_user_func_array([$query, 'where'], $arguments);
        }

        foreach ($this->pivotWhereIns as $arguments) {
            call_user_func_array([$query, 'whereIn'], $arguments);
        }

        return $query->where($this->foreignPivotKey, $this->getParentKeyValue());
    }

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param  mixed  $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            $values = [$value->getKey()];
        }

        if ($value instanceof Collection) {
            $values = $value->modelKeys();
        } elseif ($value instanceof BaseCollection) {
            $values = $value->toArray();
        }

        if (isset($values) === false) {
            $values = (array) $value;
        }

        if ($this->related->hasMutator($this->related->getKeyName())) {
            $related = $this->related;
            $values = array_map(function ($attribute) use ($related) {
                if (in_array($attribute, $this->mutated_values) === true) {
                    return $attribute;
                }

                $value = $related->serializeAttribute($related->getKeyName(), $attribute);
                $this->mutated_values[] = $value;
                return $value;
            }, $values);
        }

        return $values;
    }
}
