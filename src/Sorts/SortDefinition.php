<?php

declare(strict_types=1);

namespace Imarc\Fort\Sorts;

use Illuminate\Database\Eloquent\Builder;

abstract class SortDefinition
{
    public function __construct(
        protected ?string $column = null
    ) {}

    /**
     * @param  array{key: string, sorts?: list<array{key: string, direction: string}>}  $context
     */
    abstract public function apply(Builder $query, string $direction, array $context): void;

    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * @param  array{key: string, sorts?: list<array{key: string, direction: string}>}  $context
     */
    protected function resolveColumn(array $context): string
    {
        return $this->column ?? $context['key'];
    }

    protected function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
