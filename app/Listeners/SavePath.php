<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaperGenerated;

final class SavePath
{
    public function __construct() {}

    public function handle(PaperGenerated $event): void
    {
        $resolved = array_map(fn(string $path): array => ['CAMINHO' => $path], $event->paths);

        $event->logger->paths()->createMany($resolved);


        if ($event->paths !== []) {
            $event->logger->update(['PATH_PDF' => public_path('img/' . $event->paths[0])]);
        }
    }
}
