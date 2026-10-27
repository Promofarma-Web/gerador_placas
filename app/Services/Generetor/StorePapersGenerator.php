<?php

declare(strict_types=1);

namespace App\Services\Generetor;

use App\Dto\Payload;
use App\Dto\PayloadProduct;
use App\Enums\PrintOrigin;
use App\Models\DailyProducts;
use App\Models\Logs;
use App\Query\ClubProductsQuery;
use App\Services\RequestLogger\RequestLogger;
use Illuminate\Support\Collection;

/** Baixa as imagens e gera os PDFs de uma loja sem notificar; usado pelo products:recover */
final class StorePapersGenerator
{
    public function __construct(
        private readonly BatchLabelGenerator $generator,
        private readonly RequestLogger $requestLogger,
    ) {}

    /**
     * Fase 1: baixa para o cache as imagens da loja, sem gerar PDF; a fase 2 (handle) só lê do cache.
     * Erros de montagem do payload são reportados na fase 2.
     *
     * @return array{total: int, cached: int, downloaded: int, failed: int}
     */
    public function download(int $loja, bool $todos, string $cacheDir): array
    {
        $this->generator->image->cacheIn($cacheDir);

        $payloads = [];
        $failed = 0;

        foreach ($this->groups(DailyProducts::getDailyProducts([$loja], $todos)) as $group) {
            try {
                $payloads[] = $this->payload($group)['dto'];
            } catch (\Throwable) {
                $failed++;
            }
        }

        $result = $this->generator->image->download($payloads);
        $result['failed'] += $failed;

        return $result;
    }

    /**
     * Fase 2: gera os PDFs da loja
     *
     * @return array{
     *     generated: array<int, array{logger: \App\Models\RequestGeneratorImage, paths: array<int, string>}>,
     *     failures: array<int, string>,
     *     messages: array<int, string>,
     * }
     */
    public function handle(int $loja, bool $todos, ?int $perPaper, ?string $cacheDir): array
    {
        $result = ['generated' => [], 'failures' => [], 'messages' => []];

        /** Cache compartilhado entre os processos das lojas */
        $this->generator->image->cacheIn($cacheDir);

        try {
            $products = DailyProducts::getDailyProducts([$loja], $todos);
        } catch (\Throwable $th) {
            $result['failures'][] = "Loja {$loja}: " . $this->describe($th);

            return $result;
        }

        if ($products->isEmpty()) {
            $result['messages'][] = "Nenhum produto encontrado para a loja {$loja}";

            return $result;
        }

        foreach ($this->groups($products) as $group) {
            $first = $group->first();

            try {
                ['data' => $data, 'dto' => $dto] = $this->payload($group);
                $logger = $this->requestLogger->handle($dto, PrintOrigin::COMANDO);

                /** Os listeners de PaperGenerated salvam os caminhos; a notificação é enviada pelo comando ao final */
                $paths = $this->generator->handle($logger, $dto, true, $perPaper, notify: false);

                $result['generated'][] = ['logger' => $logger, 'paths' => $paths];

                $ids = $group->pluck('ID')->implode(', ');
                Logs::create([
                    'DATA_EXECUCAO' => now()->format('d-m-Y'),
                    'COMANDO_EXECUTADO' => json_encode(
                        array_merge(['IDS' => $ids], $data),
                        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
                    ),
                ]);
                $result['messages'][] = "Template {$first->ID_TEMPLATE} | Loja {$first->loja} gerado com sucesso. "
                    . now()->format('d/m/Y H:i:s');
            } catch (\Throwable $th) {
                $result['failures'][] = "Template {$first->ID_TEMPLATE} | Loja {$first->loja}: " . $this->describe($th);
            }
        }

        return $result;
    }

    /** Um grupo por template + loja + tipo, cada um vira uma requisição (e seus PDFs) */
    private function groups(Collection $products): Collection
    {
        return $products->groupBy(function ($product) {
            return $product->ID_TEMPLATE . '_' . $product->loja . '_' . $product->PROCFIT_TIPO;
        });
    }

    /** @return array{data: array, dto: Payload} */
    private function payload(Collection $group): array
    {
        $first = $group->first();
        $payload = $group
            ->map(function ($product) {
                return [
                    'product' => $product->PRODUTO,
                    'quantity' => $product->TOTAL_IMPRESSOES,
                    'description' => $product->DESCRICAO_REDUZIDA,
                    'ean' => $product->EAN,
                    'max_price' => $this->decimalComma($product->PRECO_MAXIMO),
                    'sail_price' => $this->decimalComma($product->PRECO_VENDA ? $product->PRECO_VENDA : $product->PRECO_MAXIMO),
                    'promotion_price' => $this->decimalComma($product->PRECO_PROMOCAO ? $product->PRECO_PROMOCAO : $product->PRECO_VENDA),
                    'percentage_discount' => $this->decimalComma($product->SUBTITULO_1),
                    'initial_date' => $product->DATA_INICIAL,
                    'final_date' => $product->DATA_FINAL,
                    'buy' => $product->LEVE,
                    'get' => $product->PAGUE,
                    'promotion_title' => $product->TITULO_PROMOCAO_2,
                    'expiration_date' => $product->VALIDADE,
                    'x' => $product->LEVE,
                    'y' => $product->PAGUE,
                    'nameplate_label_printing' => $product->ID,
                    'family' => $product->FAMILIA_PRODUTO,
                    'club_sail_price' => $this->decimalComma(
                        PayloadProduct::clubSailPrice($product->PRECO_VENDA, $product->PRECO_PROMOCAO_ORIGINAL),
                    ),
                ];
            })
            ->values()
            ->toArray();

        $data = [
            'template_id' => (int) $first->ID_TEMPLATE,
            'store' => (int) $first->loja,
            'impression_date' => now()->format('d/m/Y'),
            'type' => (int) $first->TIPO_TEMPLATE,
            'payload' => $payload,
        ];

        $dto = Payload::fromArray($data);

        return ['data' => $data, 'dto' => $dto->withClubMaxPrice(ClubProductsQuery::clubIds($dto->resultIds()))];
    }

    private function describe(\Throwable $th): string
    {
        return $th->getMessage() . ' (' . $th->getFile() . ':' . $th->getLine() . ')';
    }

    /** Troca o ponto decimal por vírgula (15.99 => 15,99) para exibir no template */
    private function decimalComma(mixed $value): ?string
    {
        return $value === null ? null : str_replace('.', ',', (string) $value);
    }
}
