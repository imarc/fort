<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests;

use Imarc\Fort\Sorts\ColumnSort;
use Imarc\Fort\Sorts\Sort;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SortTest extends TestCase
{
    public function test_map_builds_column_sorts_from_shorthand(): void
    {
        $map = Sort::map([
            'created' => 'created_at',
            'title',
        ]);

        $this->assertInstanceOf(ColumnSort::class, $map['created']);
        $this->assertInstanceOf(ColumnSort::class, $map['title']);
    }

    public function test_map_rejects_invalid_definition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Sort::map([
            'bad' => 123,
        ]);
    }
}
