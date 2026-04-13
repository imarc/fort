<?php

declare(strict_types=1);

namespace Imarc\Fort\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Imarc\Fort\Filters\FilterDefinition;
use Imarc\Fort\Sorts\SortDefinition;

class FilterableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sort = $this->input('sort');
        if (is_string($sort) && $sort !== '') {
            $this->merge([
                'sort' => [$sort],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'sort' => ['sometimes', 'array'],
            'sort.*' => ['required', 'string'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sort = $this->input('sort');
            if ($sort === null) {
                return;
            }

            if (! is_array($sort)) {
                return;
            }

            foreach ($sort as $index => $segment) {
                if (! is_string($segment)) {
                    $validator->errors()->add(
                        "sort.{$index}",
                        'Each sort value must be a string.',
                    );

                    continue;
                }

                $trim = trim($segment);
                if ($trim === '') {
                    $validator->errors()->add(
                        "sort.{$index}",
                        'Sort segments cannot be empty.',
                    );

                    continue;
                }

                $key = $trim;
                if (str_starts_with($key, '-')) {
                    $key = substr($key, 1);
                }

                if (str_starts_with($key, '+')) {
                    $key = substr($key, 1);
                }

                if ($key === '') {
                    $validator->errors()->add(
                        "sort.{$index}",
                        'Invalid sort key.',
                    );

                    continue;
                }

                if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.]*$/', $key)) {
                    $validator->errors()->add(
                        "sort.{$index}",
                        'Invalid sort key.',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->validated('filters', []);

        return is_array($filters) ? $filters : [];
    }

    /**
     * Parsed sorts for `applySorts()`: query `sort[]` when present (`-` prefix = `desc`, optional `+` = explicit `asc`);
     * when the query omits `sort`, uses `$defaults` if passed, otherwise {@see defaultSorts()}.
     * Pass an empty array to apply no sort when the query is empty (skips subclass defaults).
     *
     * @param  list<string>|null  $defaults
     * @return list<array{key: string, direction: 'asc'|'desc'}>
     */
    public function sorts(?array $defaults = null): array
    {
        $fromQuery = $this->sortsFromQuery();
        if ($fromQuery !== []) {
            return $fromQuery;
        }

        $segments = $defaults ?? $this->defaultSorts();

        return $this->parseSortSegments($segments);
    }

    /**
     * Default sort when the client omits `sort` and the controller calls `sorts()` without arguments.
     * Override in subclasses. Same string format as query segments (e.g. `'rank'`, `'+due_date'`, `'-created_at'`).
     *
     * @return list<string>
     */
    protected function defaultSorts(): array
    {
        return [];
    }

    /**
     * @return list<array{key: string, direction: 'asc'|'desc'}>
     */
    protected function sortsFromQuery(): array
    {
        $raw = $this->validated('sort', []);
        if (! is_array($raw)) {
            return [];
        }

        return $this->parseSortSegments($raw);
    }

    /**
     * @param  list<mixed>  $segments
     * @return list<array{key: string, direction: 'asc'|'desc'}>
     */
    protected function parseSortSegments(array $segments): array
    {
        $out = [];
        foreach ($segments as $segment) {
            if (! is_string($segment)) {
                continue;
            }

            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $direction = 'asc';
            if (str_starts_with($segment, '-')) {
                $direction = 'desc';
                $segment = substr($segment, 1);
            }

            if (str_starts_with($segment, '+')) {
                $segment = substr($segment, 1);
            }

            if ($segment === '' || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.]*$/', $segment)) {
                continue;
            }

            $out[] = [
                'key' => $segment,
                'direction' => $direction,
            ];
        }

        return $out;
    }

    /**
     * @return array<int|string, string|FilterDefinition>
     */
    public function filterMap(): array
    {
        return [];
    }

    /**
     * @return array<int|string, string|SortDefinition>
     */
    public function sortMap(): array
    {
        return [];
    }
}
