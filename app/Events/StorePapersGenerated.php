<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StorePapersGenerated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<int, array{logger: \App\Models\RequestGeneratorImage, paths: array<int, string>}> $items
     */
    public function __construct(
        public int $store,
        public array $items,
    ) {}
}
