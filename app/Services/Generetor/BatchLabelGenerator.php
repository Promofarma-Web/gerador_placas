<?php

declare(strict_types=1);

namespace App\Services\Generetor;

use App\Dto\Payload;
use App\Events\PaperGenerated;
use App\Models\RequestGeneratorImage;
use App\Services\Paper\PaperFactory;
use App\Services\TemplatePlacas\GenerateImage;
use Illuminate\Support\Collection;

final class BatchLabelGenerator
{
    private const PER_PAPER = 25;

    public function __construct(
        public GenerateImage $image,
    ) {}

    //
    public function handle(RequestGeneratorImage $logger, Payload $payload): array
    {


        /** Print resolve via factory qual é tipo de papel será gerado */
        $print = PaperFactory::make($payload->type);

        /** Chamada via api para template de placa devolve o base64 das imagens */
        $results = $this->image->handle($payload);

        /** Faz nivelamento do array de produtos e quebra em 25 (50 items são 2 arrays com 25 produtos cada) */
        $paths = $results
            ->chunk(self::PER_PAPER)
            ->map(fn(Collection $items, int $key): string => $print->generate($items->toArray(), sprintf(
                'print-%d-%d',
                $key + 1,
                $logger->getKey(),
            )))
            ->toArray();

        event(new PaperGenerated($logger, $paths));

        return $paths;
    }
}
