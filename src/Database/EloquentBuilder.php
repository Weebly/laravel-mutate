<?php

namespace Weebly\Mutate\Database;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

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
        if ($column instanceof Closure) {
            return parent::where($column, $operator, $value, $boolean);
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $mutatedColumn = $this->getUnqualifiedColumnName($column);
        $value = $this->model->serializeAttribute($mutatedColumn, $value);

        return parent::where($mutatedColumn, $operator, $value, $boolean);
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $mutatedColumn = $this->getUnqualifiedColumnName($column);

        $mutatedValues = [];
        foreach ($values as $value) {
            $mutatedValues[] = $this->model->serializeAttribute($mutatedColumn, $value);
        }

        return parent::whereIn($column, $mutatedValues, $boolean, $not);
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
                return $model->unserializeAttribute($mutatedColumn, $v);
            });
        } else {
            $mutatedValues = $values->mapWithKeys(function ($v, $k) use ($model, $mutatedColumn, $key) {
                return [$model->unserializeAttribute($key, $k) => $model->unserializeAttribute($mutatedColumn, $v)];
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
