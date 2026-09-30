<?php

namespace App\Console\Commands;

use App\Enums\ColorRules;
use App\Http\Controllers\GenerateImage;
use App\Models\DailyProducts;
use App\Models\Logs;
use App\Services\Generetor\BatchLabelGenerator;
use App\Services\Notification\SendNotification;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecoverProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recover {--loja= : Empresa (formato: inteiro)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle(BatchLabelGenerator $generator)
    {
        $products = DailyProducts::getDailyProducts($this->argument('loja'));

        if ($products->isEmpty()) {
            $this->error('Nenhum produto encontrado');

            return;
        }
        $byStore = $products->groupBy('loja');

        foreach ($byStore as $loja => $storeProducts) {
            $this->info("Processando loja: {$loja} - ".now()->format('d/m/Y H:i:s'));
            $paths = [];
            $grouped = $storeProducts->groupBy(function ($product) {
                return $product->ID_TEMPLATE.'_'.$product->loja;
            });

            foreach ($grouped as $group) {
                $first = $group->first();
                $payload = $group
                    ->map(function ($product) {
                        return [
                            'product' => $product->PRODUTO,
                            'quantity' => $product->TOTAL_IMPRESSOES,
                            'description' => $product->DESCRICAO_REDUZIDA,
                            'ean' => $product->EAN,
                            'max_price' => $product->PRECO_MAXIMO,
                            'sail_price' => $product->PRECO_MAXIMO,
                            'promotion_price' => $product->PRECO_PROMOCAO,
                            'percentage_discount' => $product->SUBTITULO_1,
                            'initial_date' => $product->DATA_INICIAL,
                            'final_date' => $product->DATA_FINAL,
                            'buy' => $product->LEVE,
                            'get' => $product->PAGUE,
                            'promotion_title' => $product->TITULO_PROMOCAO_2,
                            'expiration_date' => $product->VALIDADE,
                            'X' => $product->LEVE,
                            'Y' => $product->PAGUE,
                            'nameplate_label_printing' => $product->IMPRESSAO_ETIQUETA_PLACA,
                            'family' => $product->FAMILIA_PRODUTO,
                        ];
                    })
                    ->values()
                    ->toArray();

                try {
                    $request = new Request([
                        'template_id' => $first->ID_TEMPLATE,
                        'store' => $first->loja,
                        'impression_date' => now()->format('d/m/Y'),
                        'type' => $first->TIPO_TEMPLATE,
                        'payload' => $payload,
                    ]);

                    $response = $generator->handle();
                    $responseData = json_decode($response->getContent(), true);

                    if ($responseData['status'] === 'success') {
                        foreach ($responseData['pdfs'] ?? [['pdf' => $responseData['pdf']]] as $generatedPdf) {
                            $paths[] = [
                                'path' => asset('img/'.$generatedPdf['pdf']),
                                'template_id' => $first->ID_TEMPLATE,
                                'type' => $first->TIPO_TEMPLATE,
                            ];
                        }

                        $this->sendNotification($first->loja, $paths);

                        $paths = [];

                        $ids = $group->pluck('ID')->implode(', ');
                        Logs::create([
                            'DATA_EXECUCAO' => now()->format('d-m-Y'),
                            'COMANDO_EXECUTADO' => json_encode(
                                array_merge(['IDS' => $ids], $request->all()),
                                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
                            ),
                        ]);
                        $this->info(
                            "Template {$first->ID_TEMPLATE} | Loja {$first->loja} gerado com sucesso."
                                .now()->format('d/m/Y H:i:s'),
                        );
                    }
                } catch (\Throwable $th) {
                    $this->error("Erro no template {$first->ID_TEMPLATE} | Loja {$first->loja}: ".$th->getMessage());
                    continue;
                }
            }
        }
    }
}
