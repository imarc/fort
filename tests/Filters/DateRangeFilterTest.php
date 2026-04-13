<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Filters;

use Imarc\Fort\Filters\DateRangeFilter;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Tests\Fixtures\TestItem;
use Imarc\Fort\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class DateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_start_and_end_inclusively(): void
    {
        $inside = TestItem::query()->create([
            'category_id' => 1,
            'title' => 'Inside',
            'due_date' => '2026-06-15',
        ]);
        TestItem::query()->create([
            'category_id' => 1,
            'title' => 'Before',
            'due_date' => '2026-06-01',
        ]);
        TestItem::query()->create([
            'category_id' => 1,
            'title' => 'After',
            'due_date' => '2026-07-01',
        ]);

        $filter = Filter::dateRange('due_date');
        $query = TestItem::query();
        $filter->apply($query, ['start' => '2026-06-10', 'end' => '2026-06-20'], [
            'key' => 'due_date',
            'filters' => [],
        ]);

        $this->assertCount(1, $query->get());
        $this->assertSame($inside->id, $query->first()->id);
    }

    public function test_factory_matches_direct_instance(): void
    {
        $this->assertInstanceOf(DateRangeFilter::class, Filter::dateRange('due_date'));
    }

    public function test_non_array_value_is_ignored(): void
    {
        $filter = new DateRangeFilter('due_date');
        $query = TestItem::query();
        $filter->apply($query, '2026-01-01', ['key' => 'due_date', 'filters' => []]);

        $this->assertStringNotContainsString('due_date', $query->toSql());
    }
}
