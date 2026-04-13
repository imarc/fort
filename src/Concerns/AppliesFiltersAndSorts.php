<?php

declare(strict_types=1);

namespace Imarc\Fort\Concerns;

use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Filters\FilterDefinition;
use Imarc\Fort\Sorts\Sort;
use Imarc\Fort\Sorts\SortDefinition;

trait AppliesFiltersAndSorts
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int|string, string|FilterDefinition>  $definitions
     */
    public function applyFilters(array $filters = [], array $definitions = []): static
    {
        if ($filters === [] || $definitions === []) {
            return $this;
        }

        $filterMap = Filter::map($definitions);

        foreach ($filters as $key => $value) {
            $definition = $filterMap[$key] ?? null;

            if (! $definition instanceof FilterDefinition) {
                continue;
            }

            $definition->apply($this, $value, [
                'key' => $key,
                'filters' => $filters,
            ]);
        }

        return $this;
    }

    /**
     * @param  list<array{key: string, direction: string}>  $sorts
     * @param  array<int|string, string|SortDefinition>  $definitions
     */
    public function applySorts(array $sorts = [], array $definitions = []): static
    {
        if ($sorts === [] || $definitions === []) {
            return $this;
        }

        $sortMap = Sort::map($definitions);

        foreach ($sorts as $item) {
            $key = $item['key'] ?? null;
            if (! is_string($key) || $key === '') {
                continue;
            }

            $direction = $item['direction'] ?? 'asc';
            if (! is_string($direction)) {
                continue;
            }

            $normalized = strtolower($direction);

            if (! in_array($normalized, ['asc', 'desc'], true)) {
                continue;
            }

            $definition = $sortMap[$key] ?? null;

            if (! $definition instanceof SortDefinition) {
                continue;
            }

            $definition->apply($this, $normalized, [
                'key' => $key,
                'sorts' => $sorts,
            ]);
        }

        return $this;
    }
}
