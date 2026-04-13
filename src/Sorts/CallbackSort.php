<?php

declare(strict_types=1);

namespace Imarc\Fort\Sorts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class CallbackSort extends SortDefinition
{
    public function __construct(
        protected Closure $callback
    ) {
        parent::__construct(null);
    }

    /**
     * @param  array{key: string, sorts?: list<array{key: string, direction: string}>}  $context
     */
    public function apply(Builder $query, string $direction, array $context): void
    {
        ($this->callback)($query, $direction, $context);
    }
}
