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
    public function handle(RequestGeneratorImage $logger, Payload $payload, bool $isSend = false, ?int $perPaper = null): array
    {


        /** Print resolve via factory qual é tipo de papel será gerado */
        $print = PaperFactory::make($payload->type);

        /** Chamada via api para template de placa devolve o base64 das imagens */
        $results = $this->image->handle($payload);

        /** Faz nivelamento do array de produtos e quebra em 25 (50 items são 2 arrays com 25 produtos cada) */
        $chunks = $results->chunk($perPaper ?? self::PER_PAPER);

        /** Nome do pdf: print-data-loja-tipo_etiqueta (com sufixo -N quando houver mais de um pdf) */
        $filename = sprintf(
            'print-%s-%d-%s',
            now()->format('d-m-Y'),
            $payload->store,
            strtolower($payload->type->name),
        );

        $paths = $chunks
            ->map(fn(Collection $items, int $key): string => $print->generate(
                $items->toArray(),
                $chunks->count() > 1 ? sprintf('%s-%d', $filename, $key + 1) : $filename,
            ))
            ->toArray();


        PaperGenerated::dispatchIf($isSend, $logger, $paths);

        return $paths;
    }
}
