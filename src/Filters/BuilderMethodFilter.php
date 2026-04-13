<?php

declare(strict_types=1);

namespace Imarc\Fort\Filters;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;

/**
 * Invokes a named method on the query builder when it matches the expected builder class.
 *
 * Supported method shapes:
 * - no parameters (the filter value is ignored),
 * - one parameter (the filter value),
 * - two or more parameters (the filter value and the filter context array).
 */
class BuilderMethodFilter extends FilterDefinition
{
    /**
     * @param  class-string  $builderClass
     */
    public function __construct(
        protected string $builderClass,
        protected string $method,
    ) {
        parent::__construct(null);

        if ($this->method === '') {
            throw new InvalidArgumentException('Builder method name must not be empty.');
        }
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function apply(Builder $query, mixed $value, array $context): void
    {
        if (! is_a($query, $this->builderClass, true)) {
            throw new InvalidArgumentException(sprintf(
                'Filter targets %s but the query builder is %s.',
                $this->builderClass,
                $query::class,
            ));
        }

        try {
            $reflection = new ReflectionMethod($query::class, $this->method);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(
                sprintf('Builder %s has no method %s.', $query::class, $this->method),
                0,
                $e,
            );
        }

        $paramCount = count($reflection->getParameters());

        if ($paramCount === 0) {
            $query->{$this->method}();

            return;
        }

        if ($paramCount === 1) {
            $query->{$this->method}($value);

            return;
        }

        $query->{$this->method}($value, $context);
    }
}
