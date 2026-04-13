<?php

declare(strict_types=1);

namespace Imarc\Fort\Sorts;

use Illuminate\Database\Eloquent\Builder;

class ColumnSort extends SortDefinition
{
    /**
     * @param  array{key: string, sorts?: list<array{key: string, direction: string}>}  $context
     */
    public function apply(Builder $query, string $direction, array $context): void
    {
        $column = $this->resolveColumn($context);
        $dir = $this->normalizeDirection($direction);

        $query->orderBy($column, $dir);
    }
}
