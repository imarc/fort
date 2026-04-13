<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $due_date
 */
final class TestItem extends Model
{
    protected $table = 'fort_test_items';

    protected $guarded = [];

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): TestItemBuilder
    {
        return new TestItemBuilder($query);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }
}
