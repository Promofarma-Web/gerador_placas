<?php

declare(strict_types=1);

namespace App\Services\TemplatePlacas;

use App\Dto\Payload;
use App\Dto\PayloadProduct;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GenerateImage
{
    /** Requisições simultâneas à API de templates */
    private const CONCURRENCY = 16;

    /** Qualidade menor deixa a conversão, o cache e os PDFs mais leves */
    private const JPEG_QUALITY = 75;

    /** Diretório do cache de imagens; nulo desativa o cache (ativado pelo products:recover) */
    private ?string $cacheDir = null;

    /** Ativa o cache em disco: imagens com o mesmo conteúdo são geradas uma vez e reaproveitadas entre lojas */
    public function cacheIn(?string $dir): void
    {
        if ($dir !== null) {
            File::ensureDirectoryExists($dir);
        }

        $this->cacheDir = $dir;
    }

    /**
     * Baixa para o cache as imagens ainda não baixadas, sem mantê-las em memória.
     * Falhas ficam de fora do cache e são tentadas de novo (e reportadas) na geração do PDF.
     *
     * @param Payload[] $payloads
     * @return array{total: int, cached: int, downloaded: int, failed: int}
     */
    public function download(array $payloads): array
    {
        if ($this->cacheDir === null) {
            throw new RuntimeException('O download das imagens exige o cache ativo (cacheIn)');
        }

        $bodies = [];

        foreach ($payloads as $payload) {
            foreach ($this->bodies($payload) as $body) {
                $bodies[$this->key($body)] = $body;
            }
        }

        $pending = array_filter($bodies, fn(string $key): bool => ! File::exists("{$this->cacheDir}/{$key}"), ARRAY_FILTER_USE_KEY);
        $failed = 0;

        $this->request(
            $pending,
            fn(string $key, string $image) => $this->toCache($key, $image),
            function () use (&$failed): void {
                $failed++;
            },
        );

        return [
            'total' => count($bodies),
            'cached' => count($bodies) - count($pending),
            'downloaded' => count($pending) - $failed,
            'failed' => $failed,
        ];
    }

    public function handle(Payload $payload): Collection
    {
        $bodies = $this->bodies($payload);
        $keys = array_map(fn(array $body): string => $this->key($body), $bodies);

        /** Produtos com o mesmo conteúdo compartilham a chave e geram uma única requisição */
        $images = $this->fetch(array_combine($keys, $bodies));

        $results = [];

        foreach ($payload->payloads as $index => $product) {
            if (! isset($results[$product->product])) {
                $results[$product->product] = [];
            }

            array_push($results[$product->product], ...array_fill($index, max(1, $product->quantity), [
                'enconde' => $images[$keys[$index]],
            ]));
        }

        return collect($results)->collapse()->map(fn(array $result): string => $result['enconde']);
    }

    /**
     * @param array<string, array> $bodies corpo da requisição indexado pela chave do cache
     * @return array<string, string> imagem indexada pela chave do cache
     */
    private function fetch(array $bodies): array
    {
        $images = [];

        foreach ($bodies as $key => $body) {
            $cached = $this->fromCache($key);

            if ($cached !== null) {
                $images[$key] = $cached;
                unset($bodies[$key]);
            }
        }

        $this->request(
            $bodies,
            function (string $key, string $image) use (&$images): void {
                $images[$key] = $image;
                $this->toCache($key, $image);
            },
            fn(string $key, \Throwable $th) => throw $th,
        );

        return $images;
    }

    /** Corpo enviado à API para cada produto do payload */
    private function bodies(Payload $payload): array
    {
        return array_map(fn(PayloadProduct $product): array => [
            ...$payload->toArray(),
            'payload' => $product->toArray(),
        ], $payload->payloads);
    }

    /**
     * Busca as imagens na API em lotes simultâneos e entrega cada uma, já em JPEG, ao $onImage
     *
     * @param array<string, array> $bodies corpo da requisição indexado pela chave do cache
     */
    private function request(array $bodies, \Closure $onImage, \Closure $onFailure): void
    {
        foreach (array_chunk($bodies, self::CONCURRENCY, true) as $chunk) {
            $responses = Http::pool(fn(Pool $pool): array => array_map(
                fn(string $key, array $body) => $pool->as($key)
                    ->baseUrl(config('services.template_placas.url'))
                    ->acceptJson()
                    /** format=jpg: a API devolve JPEG pronto; fora da chave do cache */
                    ->post('/generate-image-product', [...$body, 'format' => 'jpg', 'quality' => self::JPEG_QUALITY]),
                array_keys($chunk),
                $chunk,
            ));

            foreach ($responses as $key => $response) {
                try {
                    $image = $this->toJpeg($this->image($response));
                } catch (\Throwable $th) {
                    $onFailure($key, $th);

                    continue;
                }

                $onImage($key, $image);
            }
        }
    }

    /** O pool devolve a exceção no lugar da resposta quando a conexão falha */
    private function image(mixed $response): string
    {
        if ($response instanceof \Throwable) {
            throw new RuntimeException('Invalid response for api: ' . $response->getMessage(), previous: $response);
        }

        if ($response->failed() || $response->json('status') !== 'success' || $response->json('image') === null) {
            throw new RuntimeException(sprintf(
                'Invalid response for api [%d]: %s',
                $response->status(),
                $response->json('image') ?? $response->json('message') ?? mb_substr($response->body(), 0, 300),
            ));
        }

        return $response->json('image');
    }

    /**
     * A API devolve PNG com transparência, que o FPDF desmonta pixel a pixel (~0,5s por etiqueta);
     * JPEG é embutido direto. A transparência vira fundo branco.
     * Versões da API sem suporte a format=jpg ainda devolvem PNG, por isso a conversão continua aqui.
     */
    private function toJpeg(string $dataUrl): string
    {
        if (str_starts_with($dataUrl, 'data:image/jpeg;')) {
            return $dataUrl;
        }

        $source = imagecreatefromstring(base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1)));

        if ($source === false) {
            throw new RuntimeException('Invalid image returned by api');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $jpeg = imagecreatetruecolor($width, $height);
        imagefill($jpeg, 0, 0, imagecolorallocate($jpeg, 255, 255, 255));
        imagecopy($jpeg, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($jpeg, null, self::JPEG_QUALITY);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    /** Loja, ID da linha e quantidade não aparecem na imagem, então ficam fora da chave */
    private function key(array $body): string
    {
        unset($body['store'], $body['payload']['nameplate_label_printing'], $body['payload']['quantity']);

        return sha1(json_encode($body));
    }

    private function fromCache(string $key): ?string
    {
        if ($this->cacheDir === null || ! File::exists("{$this->cacheDir}/{$key}")) {
            return null;
        }

        return File::get("{$this->cacheDir}/{$key}");
    }

    /** Grava em um arquivo temporário e renomeia, para outro processo nunca ler uma imagem pela metade */
    private function toCache(string $key, string $image): void
    {
        if ($this->cacheDir === null) {
            return;
        }

        $tmp = "{$this->cacheDir}/{$key}." . getmypid() . '.tmp';

        File::put($tmp, $image);
        File::move($tmp, "{$this->cacheDir}/{$key}");
    }
}
