<?php

declare(strict_types=1);

namespace Imarc\Fort\Sorts;

use Closure;
use InvalidArgumentException;

class Sort
{
    public static function column(?string $column = null): SortDefinition
    {
        $column ??= '';

        return new ColumnSort($column);
    }

    public static function callback(Closure $callback): CallbackSort
    {
        return new CallbackSort($callback);
    }

    /**
     * @param  array<int|string, string|SortDefinition>  $definitions
     * @return array<string, SortDefinition>
     */
    public static function map(array $definitions): array
    {
        $map = [];

        foreach ($definitions as $key => $definition) {
            if (is_int($key) && is_string($definition)) {
                $map[$definition] = static::column($definition);

                continue;
            }

            if (is_string($key) && is_string($definition)) {
                $map[$key] = static::column($definition);

                continue;
            }

            if (is_string($key) && $definition instanceof SortDefinition) {
                $map[$key] = $definition;

                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Invalid sort definition [%s => %s].',
                is_int($key) ? (string) $key : "'{$key}'",
                is_object($definition) ? $definition::class : gettype($definition),
            ));
        }

        return $map;
    }
}
