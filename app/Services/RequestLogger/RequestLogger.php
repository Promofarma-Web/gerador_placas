<?php

declare(strict_types=1);

namespace App\Services\RequestLogger;

use App\Dto\Payload;
use App\Dto\PayloadProduct;
use App\Enums\PrintOrigin;
use App\Models\RequestGeneratorImage;
use App\Models\RequestGeneratorImageProduct;
use Illuminate\Support\Facades\DB;

final class RequestLogger
{
    /** 4 colunas x 400 linhas fica abaixo do limite de 2100 parâmetros do SQL Server */
    private const INSERT_CHUNK = 400;

    public function handle(Payload $payload, PrintOrigin $origin = PrintOrigin::PADRAO): RequestGeneratorImage
    {


        return DB::transaction(function () use ($payload, $origin): RequestGeneratorImage {
            $request = RequestGeneratorImage::query()->create([
                ...$payload->toAttributes(),
                'ORIGEM_IMPRESSAO' => $origin->value,
            ]);

            /** Um INSERT por lote em vez de um por produto (cada ida ao banco custa ~64ms) */
            foreach (array_chunk($payload->payloads, self::INSERT_CHUNK) as $chunk) {
                RequestGeneratorImageProduct::query()->insert(array_map(fn(PayloadProduct $product): array => [
                    ...$product->toAttributes(),
                    'REQUISICAO_GERADOR_PLACAS' => $request->REQUISICAO_GERADOR_PLACAS,
                ], $chunk));
            }



            return $request;
        });
    }
}
