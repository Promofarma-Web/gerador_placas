<?php

namespace App\Console\Commands;

use App\Dto\Payload;
use App\Events\StorePapersGenerated;
use App\Models\DailyProducts;
use App\Models\Logs;
use App\Query\ClubProductsQuery;
use App\Services\Generetor\BatchLabelGenerator;
use App\Services\RequestLogger\RequestLogger;
use Illuminate\Console\Command;

class RecoverProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recover
        {--loja= : Empresa (formato: inteiro)}
        {--quantity= : Quantidade de etiquetas por folha (padrão: 25)}
        {--agrupar : Envia uma única notificação por loja com todos os PDFs gerados}
        {--todos : Ignora os produtos já gerados e gera novamente todos os produtos da loja}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle(BatchLabelGenerator $generator, RequestLogger $requestLogger)
    {
        $quantity = $this->option('quantity');

        if ($quantity !== null && (! ctype_digit((string) $quantity) || (int) $quantity < 1)) {
            $this->error('A opção --quantity deve ser um inteiro maior que zero');

            return;
        }

        $perPaper = $quantity !== null ? (int) $quantity : null;
        $agrupar = (bool) $this->option('agrupar');

        $products = DailyProducts::getDailyProducts($this->option('loja'), (bool) $this->option('todos'));

        if ($products->isEmpty()) {
            $this->error('Nenhum produto encontrado');

            return;
        }
        $byStore = $products->groupBy('loja');

        foreach ($byStore as $loja => $storeProducts) {
            $this->info("Processando loja: {$loja} - " . now()->format('d/m/Y H:i:s'));
            $grouped = $storeProducts->groupBy(function ($product) {
                return $product->ID_TEMPLATE . '_' . $product->loja . '_' . $product->PROCFIT_TIPO;
            });

            /** PDFs gerados na loja, usados na notificação agrupada */
            $generated = [];

            foreach ($grouped as $group) {
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
                        ];
                    })
                    ->values()
                    ->toArray();

                try {
                    $data = [
                        'template_id' => (int) $first->ID_TEMPLATE,
                        'store' => (int) $first->loja,
                        'impression_date' => now()->format('d/m/Y'),
                        'type' => (int) $first->TIPO_TEMPLATE,
                        'payload' => $payload,
                    ];

                    $dto = Payload::fromArray($data);
                    $dto = $dto->withClubMaxPrice(ClubProductsQuery::clubIds($dto->resultIds()));
                    $logger = $requestLogger->handle($dto);

                    /** Os listeners de PaperGenerated salvam os caminhos e, sem --agrupar, notificam a loja */
                    $paths = $generator->handle($logger, $dto, true, $perPaper, notify: ! $agrupar);

                    if ($agrupar) {
                        $generated[] = ['logger' => $logger, 'paths' => $paths];
                    }

                    $ids = $group->pluck('ID')->implode(', ');
                    Logs::create([
                        'DATA_EXECUCAO' => now()->format('d-m-Y'),
                        'COMANDO_EXECUTADO' => json_encode(
                            array_merge(['IDS' => $ids], $data),
                            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
                        ),
                    ]);
                    $this->info(
                        "Template {$first->ID_TEMPLATE} | Loja {$first->loja} gerado com sucesso. "
                            . now()->format('d/m/Y H:i:s'),
                    );
                } catch (\Throwable $th) {
                    $this->error("Erro no template {$first->ID_TEMPLATE} | Loja {$first->loja}: " . $th->getMessage());
                    continue;
                }
            }

            if ($agrupar && $generated !== []) {
                try {
                    StorePapersGenerated::dispatch((int) $loja, $generated);
                    $this->info("Notificação agrupada enviada para a loja {$loja} (" . count($generated) . ' templates)');
                } catch (\Throwable $th) {
                    $this->error("Erro ao enviar a notificação agrupada da loja {$loja}: " . $th->getMessage());
                }
            }
        }
    }

    /** Troca o ponto decimal por vírgula (15.99 => 15,99) para exibir no template */
    private function decimalComma(mixed $value): ?string
    {
        return $value === null ? null : str_replace('.', ',', (string) $value);
    }
}
