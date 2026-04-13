<?php

declare(strict_types=1);

namespace Imarc\Fort\Eloquent;

use Imarc\Fort\Concerns\AppliesFiltersAndSorts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent builder with {@see AppliesFiltersAndSorts}. Use {@see \Imarc\Fort\Eloquent\Concerns\HasFilterableQuery} on models
 * that do not need a dedicated builder class, or extend this class when adding model-specific query methods.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class FilterableBuilder extends Builder
{
    use AppliesFiltersAndSorts;
}
