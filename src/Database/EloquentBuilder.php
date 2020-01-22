<?php

namespace Weebly\Mutate\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;

class EloquentBuilder extends Builder
{
    /**
     * @var \Weebly\Mutate\Database\Model
     */
    protected $model;

    /**
     * {@inheritdoc}
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column)) {
            foreach ($column as $name => $value) {
                $this->where($name, $value);
            }

            return $this;
        }
        $bindings = $this->query->bindings;

        parent::where($column, $operator, $value, $boolean);

        // Remove the last item of the array
        $where = array_pop($this->query->wheres);

        if ($where['type'] === 'Nested' || $where['type'] === 'Null') {
            // Add where statement back and return
            $this->query->wheres[] = $where;

            return $this;
        }

        // Get the column name
        $mutatedColumn = $this->getUnqualifiedColumnName($where['column']);

        // Modify the values
        $where['value'] = $this->model->serializeAttribute($mutatedColumn, $where['value']);

        // Add where statement back
        $this->query->wheres[] = $where;

        // Add the mutated bindings
        if (! $where['value'] instanceof Expression) {
            // Reset the bindings to the previous value
            $this->query->bindings = $bindings;

            // Add the mutated bindings back
            $this->addBinding($where['value'], 'where');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereKey($id)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            $this->whereIn($this->model->getQualifiedKeyName(), $id);

            return $this;
        }

        return parent::whereKey($id);
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $bindings = $this->query->bindings;

        parent::whereIn($column, $values, $boolean, $not);

        // Remove the last item of the array
        $where = array_pop($this->query->wheres);

        if ($where['type'] === 'Nested') {
            // Add where statement back and return
            $this->query->wheres[] = $where;

            return $this;
        }

        // Get the column name
        $mutatedColumn = $this->getUnqualifiedColumnName($where['column']);

        // Loop over all values and mutate them
        $mutatedValues = [];
        foreach ($where['values'] as $value) {
            if ($value instanceof Expression) {
                $mutatedValues[] = $value;
            } else {
                $mutatedValues[] = $this->model->serializeAttribute($mutatedColumn, $value);
            }
        }

        // Modify the values
        $where['values'] = $mutatedValues;

        // Add where statement back
        $this->query->wheres[] = $where;

        // Reset the bindings to the previous value
        $this->query->bindings = $bindings;
        // Loop over all the mutated values and add the bindings
        foreach ($mutatedValues as $value) {
            if (! $value instanceof Expression) {

                // Add the mutated bindings back
                $this->addBinding($value, 'where');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $values)
    {
        // Determine which values have mutators.
        $mutable = array_keys(array_intersect_key($this->model->getMutators(), $values));

        $update = [];
        foreach ($mutable as $mutate) {
            // We only need to mutate the attribute if it is marked as clean otherwise, we will
            // be mutating the attribute twice.
            if ($this->model->isClean($mutate)) {
                $update[$mutate] = $this->model->serializeAttribute($mutate, $values[$mutate]);
            }
        }

        // Merge the mutated values into the original values
        return parent::update(array_merge($values, $update));
    }

    /**
     * {@inheritdoc}
     */
    public function pluck($column, $key = null)
    {
        $values = parent::pluck($column, $key);
        $mutatedColumn = $this->getUnqualifiedColumnName($column);

        $model = $this->model;
        if (is_null($key)) {
            $mutatedValues = $values->map(function ($v) use ($model, $mutatedColumn) {
                return $model->unserializeAttribute($mutatedColumn, $v, true);
            });
        } else {
            $mutatedValues = $values->mapWithKeys(function ($v, $k) use ($model, $mutatedColumn, $key) {
                return [$model->unserializeAttribute($key, $k, true) => $model->unserializeAttribute($mutatedColumn, $v, true)];
            });
        }

        return $mutatedValues;
    }

    /**
     * Get the column name without any table prefix.
     *
     * @param string $column
     * @return string
     */
    protected function getUnqualifiedColumnName($column)
    {
        if (Str::contains($column, '.')) {
            return Str::after($column, '.');
        }

        return $column;
    }
}
