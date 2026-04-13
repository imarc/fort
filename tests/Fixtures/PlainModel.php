<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Model without a custom Eloquent builder (uses default {@see \Illuminate\Database\Eloquent\Builder}).
 */
final class PlainModel extends Model
{
    protected $table = 'fort_test_items';

    protected $guarded = [];
}
