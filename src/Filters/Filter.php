<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Closure;
use InvalidArgumentException;

class Filter
{
    public static function exact(?string $column = null): FilterDefinition
    {
        $column ??= '';

        if (str_contains($column, '.')) {
            return RelationExactFilter::fromString($column);
        }

        return new ExactFilter($column);
    }

    public static function relationExact(string $relation, string $column): RelationExactFilter
    {
        return new RelationExactFilter($relation, $column);
    }

    public static function callback(Closure $callback): CallbackFilter
    {
        return new CallbackFilter($callback);
    }

    /**
     * Run a method on the query builder when it is an instance of {@see $builderClass}.
     *
     * The method may take zero arguments, only the filter value, or the value plus the context array
     * (same shape as {@see CallbackFilter}: `key`, `filters`).
     *
     * @param  class-string  $builderClass
     */
    public static function builder(string $builderClass, string $method): BuilderMethodFilter
    {
        return new BuilderMethodFilter($builderClass, $method);
    }

    public static function dateRange(string $column): DateRangeFilter
    {
        return new DateRangeFilter($column);
    }

    /**
     * @param  array<int|string, string|FilterDefinition>  $definitions
     * @return array<string, FilterDefinition>
     */
    public static function map(array $definitions): array
    {
        $map = [];

        foreach ($definitions as $key => $definition) {
            if (is_int($key) && is_string($definition)) {
                $map[$definition] = static::exact($definition);

                continue;
            }

            if (is_string($key) && is_string($definition)) {
                $map[$key] = static::exact($definition);

                continue;
            }

            if (is_string($key) && $definition instanceof FilterDefinition) {
                $map[$key] = $definition;

                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Invalid filter definition [%s => %s].',
                is_int($key) ? $key : "'{$key}'",
                is_object($definition) ? $definition::class : gettype($definition),
            ));
        }

        return $map;
    }
}
