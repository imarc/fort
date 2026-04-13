<?php

declare(strict_types=1);

namespace Imarc\Fort\Eloquent\Concerns;

use Imarc\Fort\Eloquent\FilterableBuilder;

/**
 * Use on {@see \Illuminate\Database\Eloquent\Model} classes so {@see \Illuminate\Database\Eloquent\Model::query()}
 * returns a {@see FilterableBuilder} with {@see \Imarc\Fort\Concerns\AppliesFiltersAndSorts}.
 *
 * When you need custom builder methods, prefer extending {@see FilterableBuilder} and overriding
 * {@see \Illuminate\Database\Eloquent\Model::newEloquentBuilder()} to return that subclass instead.
 */
trait HasFilterableQuery
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): FilterableBuilder
    {
        return new FilterableBuilder($query);
    }
}
