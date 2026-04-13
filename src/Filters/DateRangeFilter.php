<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inclusive date range on a column. Expects {@see $value} as an array with optional
 * `start` and/or `end` keys, each `YYYY-MM-DD`.
 */
class DateRangeFilter extends FilterDefinition
{
    public const DATE_FORMAT = 'Y-m-d';

    public function __construct(string $column)
    {
        parent::__construct($column);
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function apply(Builder $query, mixed $value, array $context): void
    {
        if (! is_array($value)) {
            return;
        }

        $column = $this->resolveColumn($context);
        $start = $this->parseBound($value['start'] ?? null);
        $end = $this->parseBound($value['end'] ?? null);

        if ($start === null && $end === null) {
            return;
        }

        if ($start !== null) {
            $query->whereDate($column, '>=', $start);
        }

        if ($end !== null) {
            $query->whereDate($column, '<=', $end);
        }
    }

    private function parseBound(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $trimmed);
        if ($parsed === false || $parsed->format(self::DATE_FORMAT) !== $trimmed) {
            return null;
        }

        return $trimmed;
    }
}
