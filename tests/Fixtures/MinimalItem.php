<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Fixtures;

use Imarc\Fort\Eloquent\Concerns\HasFilterableQuery;
use Illuminate\Database\Eloquent\Model;

/**
 * Model that uses the package shared builder via {@see HasFilterableQuery} only (no custom builder class).
 *
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 */
final class MinimalItem extends Model
{
    use HasFilterableQuery;

    protected $table = 'fort_test_items';

    protected $guarded = [];
}
