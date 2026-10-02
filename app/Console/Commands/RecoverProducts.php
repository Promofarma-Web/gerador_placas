<?php

namespace App\Console\Commands;

use App\Dto\Payload;
use App\Models\DailyProducts;
use App\Models\Logs;
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
        {--quantidade= : Quantidade de etiquetas por folha (padrão: 25)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle(BatchLabelGenerator $generator, RequestLogger $requestLogger)
    {
        $quantidade = $this->option('quantidade');

        if ($quantidade !== null && (! ctype_digit((string) $quantidade) || (int) $quantidade < 1)) {
            $this->error('A opção --quantidade deve ser um inteiro maior que zero');

            return;
        }

        $perPaper = $quantidade !== null ? (int) $quantidade : null;

        $products = DailyProducts::getDailyProducts($this->option('loja'))->take(1);

        if ($products->isEmpty()) {
            $this->error('Nenhum produto encontrado');

            return;
        }
        $byStore = $products->groupBy('loja');

        foreach ($byStore as $loja => $storeProducts) {
            $this->info("Processando loja: {$loja} - " . now()->format('d/m/Y H:i:s'));
            $grouped = $storeProducts->groupBy(function ($product) {
                return $product->ID_TEMPLATE . '_' . $product->loja;
            });

            foreach ($grouped as $group) {
                $first = $group->first();
                $payload = $group
                    ->map(function ($product) {
                        return [
                            'product' => $product->PRODUTO,
                            'quantidade' => $product->TOTAL_IMPRESSOES,
                            'description' => $product->DESCRICAO_REDUZIDA,
                            'ean' => $product->EAN,
                            'max_price' => $product->PRECO_MAXIMO,
                            'sail_price' => $product->PRECO_VENDA ? $product->PRECO_VENDA : $product->PRECO_MAXIMO,
                            'promotion_price' => $product->PRECO_PROMOCAO ? $product->PRECO_PROMOCAO : $product->PRECO_VENDA,
                            'percentage_discount' => $product->SUBTITULO_1,
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
                    $logger = $requestLogger->handle($dto);

                    /** Os listeners de PaperGenerated salvam os caminhos e notificam a loja */

                    $generator->handle($logger, $dto, true, $perPaper);

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
        }
    }
}
