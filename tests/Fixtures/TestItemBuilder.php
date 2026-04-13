<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests\Fixtures;

use Imarc\Fort\Eloquent\FilterableBuilder;

/**
 * @extends FilterableBuilder<TestItem>
 */
final class TestItemBuilder extends FilterableBuilder
{
    public function forCategory(int $categoryId): static
    {
        return $this->where('category_id', $categoryId);
    }

    /**
     * @param  array{key: string, filters?: array<string, mixed>}  $context
     */
    public function whereTitleContains(string $needle, array $context): static
    {
        return $this->where('title', 'like', '%'.$needle.'%');
    }
}
