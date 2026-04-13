<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Eloquent;

use Imarc\Fort\Eloquent\FilterableBuilder;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Tests\Fixtures\MinimalItem;
use Imarc\Fort\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class HasFilterableQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_returns_filterable_builder(): void
    {
        $query = MinimalItem::query();

        $this->assertInstanceOf(FilterableBuilder::class, $query);
    }

    public function test_apply_filters_without_custom_builder_class(): void
    {
        MinimalItem::query()->create([
            'category_id' => 1,
            'title' => 'Match',
            'due_date' => null,
        ]);
        MinimalItem::query()->create([
            'category_id' => 2,
            'title' => 'Other',
            'due_date' => null,
        ]);

        $rows = MinimalItem::query()
            ->applyFilters(['category_id' => 1], ['category_id'])
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Match', $rows->first()->title);
    }

    public function test_apply_filters_with_filter_factory_map(): void
    {
        MinimalItem::query()->create([
            'category_id' => 1,
            'title' => 'A',
            'due_date' => null,
        ]);

        $rows = MinimalItem::query()
            ->applyFilters(['category_id' => 1], Filter::map(['category_id']))
            ->get();

        $this->assertCount(1, $rows);
    }
}
