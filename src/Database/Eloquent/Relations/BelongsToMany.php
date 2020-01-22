<?php

namespace Weebly\Mutate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Support\Collection as BaseCollection;

class BelongsToMany extends EloquentBelongsToMany
{
    /**
     * Local cache of mutated attributes.
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
        if (! is_null($value) && $this->parent->hasMutator($this->parentKey)) {
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

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|array  $ids
     * @param  bool   $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        // First we need to attach any of the associated models that are not currently
        // in this joining table. We'll spin through the given IDs, checking to see
        // if they exist in the array of current ones, and if not we will insert.
        $current = $this->newPivotQuery()->pluck(
            $this->relatedPivotKey
        )->all();

        if ($this->related->hasMutator($this->related->getKeyName())) {
            $related = $this->related;
            $current = array_map(function ($id) use ($related) {
                return $related->unserializeAttribute($related->getKeyName(), $id);
            }, $current);
        }

        $detach = array_diff($current, array_keys(
            $records = $this->formatRecordsList($this->parseIds($ids))
        ));

        // Next, we will take the differences of the currents and given IDs and detach
        // all of the entities that exist in the "current" array but are not in the
        // array of the new IDs given to the method which will complete the sync.
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);

            $changes['detached'] = $this->castKeys($detach);
        }

        // Now we are finally ready to attach the new records. Note that we'll disable
        // touching until after the entire operation is complete so we don't fire a
        // ton of touch operations until we are totally done syncing the records.
        $changes = array_merge(
            $changes, $this->attachNew($records, $current, false)
        );

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if (count($changes['attached']) ||
            count($changes['updated'])) {
            $this->touchIfTouching();
        }

        return $changes;
    }
}
