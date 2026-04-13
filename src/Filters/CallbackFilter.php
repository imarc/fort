<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class CallbackFilter extends FilterDefinition
{
    public function __construct(
        protected Closure $callback
    ) {
        parent::__construct(null);
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function apply(Builder $query, mixed $value, array $context): void
    {
        ($this->callback)($query, $value, $context);
    }
}
