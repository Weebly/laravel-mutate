<?php

namespace Weebly\Mutate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Model;

class BelongsToMany extends EloquentBelongsToMany
{
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
        }

        if ($value instanceof BaseCollection) {
            $values = $value->toArray();
        }

        if (isset($values) === false) {
            $values = (array) $value;
        }

        if ($this->related->hasMutator($this->related->getKeyName())) {
            $related = $this->related;
            $values = array_map(function ($attribute) use ($related) {
                return $related->serializeAttribute($related->getKeyName(), $attribute);
            }, $values);
        }

        return $values;
    }
}
