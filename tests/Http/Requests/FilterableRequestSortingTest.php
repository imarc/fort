<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Http\Requests;

use Imarc\Fort\Http\Requests\FilterableRequest;
use Imarc\Fort\Tests\TestCase;

final class FilterableRequestSortingTest extends TestCase
{
    public function test_parse_sort_segments_respects_hyphen_prefix(): void
    {
        $request = new class extends FilterableRequest
        {
            public function exposeParse(array $segments): array
            {
                return $this->parseSortSegments($segments);
            }
        };

        $parsed = $request->exposeParse(['rank', '-due_date', '-created_at']);

        $this->assertSame(
            [
                ['key' => 'rank', 'direction' => 'asc'],
                ['key' => 'due_date', 'direction' => 'desc'],
                ['key' => 'created_at', 'direction' => 'desc'],
            ],
            $parsed,
        );
    }

    public function test_parse_sort_segments_strips_plus_prefix_for_explicit_asc(): void
    {
        $request = new class extends FilterableRequest
        {
            public function exposeParse(array $segments): array
            {
                return $this->parseSortSegments($segments);
            }
        };

        $parsed = $request->exposeParse(['+rank', '+due_date', 'plain']);

        $this->assertSame(
            [
                ['key' => 'rank', 'direction' => 'asc'],
                ['key' => 'due_date', 'direction' => 'asc'],
                ['key' => 'plain', 'direction' => 'asc'],
            ],
            $parsed,
        );
    }

    public function test_parse_sort_segments_hyphen_then_plus_still_descends(): void
    {
        $request = new class extends FilterableRequest
        {
            public function exposeParse(array $segments): array
            {
                return $this->parseSortSegments($segments);
            }
        };

        $parsed = $request->exposeParse(['-+due_date']);

        $this->assertSame(
            [['key' => 'due_date', 'direction' => 'desc']],
            $parsed,
        );
    }
}
