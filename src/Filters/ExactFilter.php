<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Illuminate\Database\Eloquent\Builder;

class ExactFilter extends FilterDefinition
{
    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function apply(Builder $query, mixed $value, array $context): void
    {
        $column = $this->resolveColumn($context);
        $value = $this->normalizeScalar($value);

        if ($value === null) {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }
}
