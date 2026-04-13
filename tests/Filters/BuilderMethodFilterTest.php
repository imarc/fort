<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Filters;

use Imarc\Fort\Filters\BuilderMethodFilter;
use Imarc\Fort\Filters\Filter;
use Imarc\Fort\Tests\Fixtures\PlainModel;
use Imarc\Fort\Tests\Fixtures\TestItem;
use Imarc\Fort\Tests\Fixtures\TestItemBuilder;
use Imarc\Fort\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

final class BuilderMethodFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_one_parameter_builder_method(): void
    {
        TestItem::query()->create([
            'category_id' => 1,
            'title' => 'Keep',
            'due_date' => null,
        ]);
        TestItem::query()->create([
            'category_id' => 2,
            'title' => 'Drop',
            'due_date' => null,
        ]);

        $filter = Filter::builder(TestItemBuilder::class, 'forCategory');

        $query = TestItem::query();
        $filter->apply($query, 1, [
            'key' => 'category_id',
            'filters' => ['category_id' => 1],
        ]);

        $this->assertCount(1, $query->get());
        $this->assertSame('Keep', $query->first()->title);
    }

    public function test_applies_two_parameter_builder_method(): void
    {
        TestItem::query()->create([
            'category_id' => 1,
            'title' => 'Wireframe draft',
            'due_date' => null,
        ]);
        TestItem::query()->create([
            'category_id' => 1,
            'title' => 'Other',
            'due_date' => null,
        ]);

        $filter = Filter::builder(TestItemBuilder::class, 'whereTitleContains');

        $query = TestItem::query()->forCategory(1);
        $context = ['key' => 'search', 'filters' => ['search' => 'wire']];
        $filter->apply($query, 'wire', $context);

        $this->assertCount(1, $query->get());
        $this->assertSame('Wireframe draft', $query->first()->title);
    }

    public function test_throws_when_query_builder_class_does_not_match(): void
    {
        $filter = Filter::builder(TestItemBuilder::class, 'forCategory');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TestItemBuilder');

        $filter->apply(PlainModel::query(), 1, ['key' => 'x', 'filters' => []]);
    }

    public function test_throws_when_method_does_not_exist(): void
    {
        $filter = new BuilderMethodFilter(TestItemBuilder::class, 'notAMethodOnTestItemBuilder');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('notAMethodOnTestItemBuilder');

        $filter->apply(TestItem::query(), null, ['key' => 'k', 'filters' => []]);
    }
}
