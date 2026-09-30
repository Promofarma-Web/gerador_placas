<?php

declare(strict_types=1);

namespace App\Services\TemplatePlacas;

use App\Dto\Payload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GenerateImage
{
    public function handle(Payload $payload): Collection
    {
        $results = [];

        /** @var \App\Dto\PayloadProduct $product */
        foreach ($payload->payloads as $index => $product) {

            $response = Http::baseUrl(config('services.template_placas.url'))
                ->acceptJson()
                ->post('/generate-image-product', [
                    ...$payload->toArray(),
                    'payload' => $product->toArray(),
                ]);

            if ($response->failed() || $response->json('status') !== 'success' || $response->json('image') === null) {
                throw new RuntimeException(sprintf(
                    'Invalid response for api [%d]: %s',
                    $response->status(),
                    $response->json('image') ?? $response->json('message') ?? mb_substr($response->body(), 0, 300),
                ));
            }

            if (! isset($results[$product->product])) {
                $results[$product->product] = [];
            }

            array_push($results[$product->product], ...array_fill($index, max(1, $product->quantity), [
                'enconde' => $response->json('image'),
            ]));
        }

        return collect($results)->collapse()->map(fn(array $result): string => $result['enconde']);
    }
}
