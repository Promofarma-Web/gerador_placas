<?php

declare(strict_types=1);

namespace App\Services\RequestLogger;

use App\Dto\Payload;
use App\Dto\PayloadProduct;
use App\Enums\PrintOrigin;
use App\Models\RequestGeneratorImage;
use Illuminate\Support\Facades\DB;

final class RequestLogger
{
    public function handle(Payload $payload, PrintOrigin $origin = PrintOrigin::PADRAO): RequestGeneratorImage
    {


        return DB::transaction(function () use ($payload, $origin): RequestGeneratorImage {
            $request = RequestGeneratorImage::query()->create([
                ...$payload->toAttributes(),
                'ORIGEM_IMPRESSAO' => $origin->value,
            ]);

            $request
                ->products()
                ->createMany(array_map(fn(PayloadProduct $product) => $product->toAttributes(), $payload->payloads));



            return $request;
        });
    }
}
