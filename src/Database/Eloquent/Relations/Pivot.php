<?php

namespace Weebly\Mutate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Weebly\Mutate\Database\Traits\HasMutators;

class Pivot extends EloquentPivot
{
    use HasMutators;
}
