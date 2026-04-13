<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class FilterDefinition
{
    public function __construct(
        protected ?string $column = null
    ) {}

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    abstract public function apply(Builder $query, mixed $value, array $context): void;

    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    protected function resolveColumn(array $context): string
    {
        return $this->column ?? $context['key'];
    }

    protected function normalizeScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}
