<?php

declare(strict_types=1);

namespace App\Services\RequestLogger;

use App\Dto\Payload;
use App\Dto\PayloadProduct;
use App\Models\RequestGeneratorImage;
use Illuminate\Support\Facades\DB;

final class RequestLogger
{
    public function handle(Payload $payload): RequestGeneratorImage
    {


        return DB::transaction(function () use ($payload): RequestGeneratorImage {
            $request = RequestGeneratorImage::query()->create($payload->toAttributes());

            $request
                ->products()
                ->createMany(array_map(fn(PayloadProduct $product) => $product->toAttributes(), $payload->payloads));



            return $request;
        });
    }
}
