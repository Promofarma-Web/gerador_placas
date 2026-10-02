<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\RequestGeneratorImage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaperGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RequestGeneratorImage $logger,
        public array $paths,
        /** false quando a notificação será enviada agrupada por loja (StorePapersGenerated) */
        public bool $notify = true,
    ) {}
}
