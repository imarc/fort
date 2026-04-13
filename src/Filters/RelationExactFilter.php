<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class RelationExactFilter extends FilterDefinition
{
    public function __construct(
        protected string $relation,
        protected string $relationColumn
    ) {
        parent::__construct(null);
    }

    public static function fromString(string $path): self
    {
        $segments = explode('.', $path);

        if (count($segments) < 2) {
            throw new InvalidArgumentException(sprintf(
                'RelationExactFilter expects at least one relationship segment and one column segment. [%s] given.',
                $path
            ));
        }

        $column = array_pop($segments);

        if (! is_string($column) || $column === '') {
            throw new InvalidArgumentException(sprintf(
                'RelationExactFilter could not determine a valid column from [%s].',
                $path
            ));
        }

        $relation = implode('.', $segments);

        if ($relation === '') {
            throw new InvalidArgumentException(sprintf(
                'RelationExactFilter could not determine a valid relation path from [%s].',
                $path
            ));
        }

        return new self($relation, $column);
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function apply(Builder $query, mixed $value, array $context): void
    {
        $value = $this->normalizeScalar($value);

        $query->whereHas($this->relation, function (Builder $relationQuery) use ($value) {
            if ($value === null) {
                $relationQuery->whereNull($this->relationColumn);

                return;
            }

            $relationQuery->where($this->relationColumn, $value);
        });
    }
}
