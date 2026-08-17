<?php

namespace App\Console\Commands;


use App\Console\Commands\SendNotification;
use App\Http\Controllers\GenerateImage;
use App\Models\DailyProducts;
use App\Models\Logs;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\ColorRules;


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

    /**
     * The console command description.
     *
     * @var integer
     */
    protected $loja;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->loja = $this->option('loja');

        $products = $this->getAllDailyProducts($this->loja);



        if ($products->isEmpty()) {
            $this->error('Nenhum produto encontrado');
            return;
        }
        $byStore = $products->groupBy('loja');

        foreach ($byStore as $loja => $storeProducts) {
            $this->info("Processando loja: {$loja} - " . now()->format('d/m/Y H:i:s'));
            $paths = [];
            $grouped = $storeProducts->groupBy(function ($product) {
                return $product->ID_TEMPLATE . '_' . $product->loja;
            });

            foreach ($grouped as $group) {
                $first = $group->first();
                $payload = $group->map(function ($product) {
                    return [
                        "product"             => $product->PRODUTO,
                        "quantity"            => $product->TOTAL_IMPRESSOES,

                        "description"         => $product->DESCRICAO_REDUZIDA,
                        "ean"                 =>  $product->EAN,
                        "max_price"           => $product->PRECO_MAXIMO,
                        "sail_price"          => $product->PRECO_MAXIMO,
                        "promotion_price"     => $product->PRECO_PROMOCAO,
                        "percentage_discount" => $product->SUBTITULO_1,
                        "initial_date"        => $product->DATA_INICIAL,
                        "final_date"          => $product->DATA_FINAL,
                        "buy"                 => $product->LEVE,
                        "get"                 => $product->PAGUE,
                        "promotion_title"     => $product->TITULO_PROMOCAO_2,
                        "expiration_date"     => $product->VALIDADE,
                        "X"                   => $product->LEVE,
                        "Y"                   => $product->PAGUE,
                        'nameplate_label_printing' => $product->IMPRESSAO_ETIQUETA_PLACA
                    ];
                })->values()->toArray();



                try {
                    $request = new Request([
                        'template_id'     => $first->ID_TEMPLATE,
                        'store'           => $first->loja,
                        'impression_date' => now()->format('d/m/Y'),
                        'type'            => $first->TIPO_TEMPLATE,
                        'payload'         => $payload,
                    ]);

                    $response     = app(GenerateImage::class)($request);
                    $responseData = json_decode($response->getContent(), true);



                    if ($responseData['status'] === 'success') {
                        foreach ($responseData['pdfs'] ?? [['pdf' => $responseData['pdf']]] as $generatedPdf) {
                            $paths[] = [
                                'path' =>  asset(' public/img/' . $generatedPdf['pdf']),
                                'template_id' => $first->ID_TEMPLATE,
                                'type' => $first->TIPO_TEMPLATE
                            ];
                        }

                        $this->sendNotification(1, $paths);

                        $paths = [];

                        $ids = $group->pluck('ID')->implode(', ');
                        Logs::create([
                            'DATA_EXECUCAO'     => now()->format('d-m-Y'),
                            'COMANDO_EXECUTADO' => json_encode(
                                array_merge(['IDS' => $ids], $request->all()),
                                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                            ),
                        ]);
                        $this->info("Template {$first->ID_TEMPLATE} | Loja {$first->loja} gerado com sucesso." . now()->format('d/m/Y H:i:s'));
                    }
                } catch (\Throwable $th) {

                    $this->error("Erro no template {$first->ID_TEMPLATE} | Loja {$first->loja}: " . $th->getMessage());
                    continue;
                }
            }
        }
    }

    public function getAllDailyProducts($loja)
    {
        $products = DailyProducts::getDailyProducts($loja);
        return $products;
    }

    public function sendNotification($loja, $paths)
    {


        $content = "<p>Bom dia, loja {$loja}.</br>Devido à precificação, alguns produtos tiveram mudanças em seus preços. Abaixo você pode conferir as etiquetas criadas, separadas por template. Obrigado pela sua atenção.</p>";
        if (!empty($paths)) {
            $templateIds = array_column($paths, 'template_id');
            $templates = DB::connection('sqlsrv')
                ->table('MK_TEMPLATES')
                ->whereIn('TEMPLATE_ID', $templateIds)
                ->get()
                ->keyBy('TEMPLATE_ID');

            $content .= "
        <table style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>
            <thead>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Tipo da Folha</th>
                    <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Título</th>
                    <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>PDF</th>
                </tr>
            </thead>
            <tbody>";

            foreach ($paths as $item) {
                $titulo = $templates[$item['template_id']]->TITULO ?? "Template {$item['template_id']}";
                $label = ColorRules::getLabel($item['type'], $item['template_id']);
                $content .= "
                <tr>
                    <td style='border: 1px solid #ddd; padding: 10px; color: {$label['color']}; background-color: {$label['background-color']}'>Folha A4 Picotada - {$label['title']}</td>
                    <td style='border: 1px solid #ddd; padding: 10px;'>{$titulo}</td>
                    <td style='border: 1px solid #ddd; padding: 10px;'>
                    <a href='{$item['path']}' target='_blank'>Clique para abrir PDF</a>
                    </td>
                </tr>";
            }

            $content .= "
            </tbody>
        </table>";
        }



        try {
            $notification = new SendNotification();

            $response = $notification->notification([
                'title'         => "Precificação - Loja {$loja}",
                'content'       => $content,
                'category_id'   => 13,
                'user_id'       => 2,
                'recipient_ids' => [$loja],
            ]);

            if ($response->successful()) {
                $this->info("Notificação enviada para loja {$loja}");
            } else {
                $this->error("Falha ao enviar notificação loja {$loja}: " . $response->body());
            }
        } catch (\Throwable $e) {
            $this->error("Erro ao enviar notificação loja {$loja}: " . $e->getMessage());
        }
    }
}
