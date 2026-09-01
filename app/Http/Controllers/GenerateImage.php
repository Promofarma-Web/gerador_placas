<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RequestGeneratorImage;
use App\Models\RequestGeneratorImageProduct;
use App\Models\TypePromotions;
use App\Services\LabelService;
use App\Services\PdfService;
use App\Services\TemplateService;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GenerateImage extends Controller
{
    private const PRODUCTS_PER_PDF = 25;

    public function __invoke(Request $request)
    {
        $payloads = $request->payload;



        try {
            $request->validate([
                'payload' => ['required', 'array'],

            ]);
        } catch (\Throwable $th) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Produto(s) inválidos',
                'errors' => $th->getMessage()
            ], 422);
        }

        $requisicaoId = (string) \Illuminate\Support\Str::uuid();
        $chunks       = array_chunk($payloads, self::PRODUCTS_PER_PDF);
        $isPaginated  = count($chunks) > 1;

        $allResults = [];
        $pdfs       = [];

        foreach ($chunks as $chunkIndex => $chunkPayloads) {
            $chunkRequisicaoId = $isPaginated
                ? sprintf('%s-%02d', $requisicaoId, $chunkIndex + 1)
                : $requisicaoId;

            $master = RequestGeneratorImage::firstOrCreate(
                [
                    'TEMPLATE_ID'     => $request->template_id,
                    'LOJA'            => $request->store,
                    'DATA_REQUISICAO' => now()->format('d-m-Y'),
                    'REQUISICAO'      => $chunkRequisicaoId,
                ],
                ['HORA_REQUISICAO' => now()->format('H:i')]
            );

            $products = [];

            foreach ($chunkPayloads as $index => $payload) {
                try {
                    $products[$index] = RequestGeneratorImageProduct::create([
                        'REQUISICAO_GERADOR_PLACAS'    => $master->getKey(),
                        'PRODUTO'                      => $payload['product'],
                        'LOJA'                         => $request->store,
                        'FAMILIA'                      => $payload['family'],
                        'ETIQUETA_PLACAS_RESULTADO_ID' => $payload['nameplate_label_printing'] ?? null,
                    ]);
                } catch (\Throwable $th) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Failed to store request: ' . $th->getMessage(),
                    ]);
                }
            }

            $imageResponses = Http::pool(fn(Pool $pool) => collect($chunkPayloads)->map(
                fn($payload) => $pool->acceptJson()->post(TemplateService::GENERATE_IMAGE_URL, [
                    'template_id'     => $request->template_id,
                    'store'           => $request->store,
                    'type'            => $request->type,
                    'impression_date' => !empty($request->impression_date)  ?  "Impresso em: " . $request->impression_date : "",
                    'payload'         => [
                        'product'                  => $payload['product'],
                        'quantity'                 => $payload['quantity'],
                        'description'              => $payload['description'],
                        'barcode'                  => $payload['ean'],
                        'ean'                      => !empty($payload['ean']) ? "EAN: " . $payload['ean'] : "",
                        'max_price'                => $payload['max_price'],
                        'sail_price'               => !empty($payload['sail_price']) ? "De: " . $payload['sail_price'] : "",
                        'promotion_price'          => !empty($payload['promotion_price']) ? "Por: " . $payload['promotion_price'] : "",
                        'percentage_discount'      => $payload['percentage_discount'],
                        'initial_date'             => $payload['initial_date'],
                        'final_date'               => $payload['final_date'],
                        'buy'                      => $payload['buy'],
                        'get'                      => $payload['get'],
                        'promotion_title'          => $payload['promotion_title'],
                        'expiration_date'          => $payload['expiration_date'],
                        'X'                        => $payload['X'],
                        'Y'                        => $payload['Y'],
                        'nameplate_label_printing' => $payload['nameplate_label_printing'],
                    ],
                ])
            )->all());

            $results = [];

            foreach ($chunkPayloads as $index => $payload) {
                $imageResponse = $imageResponses[$index];

                if ($imageResponse instanceof \Throwable) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Falha ao conectar ao serviço de geração de imagem',
                        'product' => $payload['product'],
                        'errors'  => $imageResponse->getMessage(),
                    ], 500);
                }

                if ($imageResponse->failed() || $imageResponse->json('status') === 'error') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Falha ao gerar imagem',
                        'product' => $payload['product'],
                        'code'    => $imageResponse->status(),
                        'body'    => $imageResponse->json() ?? $imageResponse->body(),
                    ], 500);
                }

                $base64Image = $imageResponse->json('image');

                if (empty($base64Image)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Serviço de geração de imagem não retornou uma imagem válida para o produto',
                        'product' => $payload['product'],
                        'body'    => $imageResponse->json(),
                    ], 500);
                }

                $quantity = max(1, (int) ($payload['quantity'] ?? 1));

                for ($i = 0; $i < $quantity; $i++) {
                    $results[] = [
                        'product'     => $payload['product'],
                        'imageBase64' => $base64Image,
                        'id'          => $products[$index]->REQUISICAO_GERADOR_PLACAS,
                    ];
                }
            }

            $printResponse = $this->print(
                new Request([
                    'id'          => $results[0]['id'],
                    'type'        =>  $request->type,
                    'imageBase64' => array_column($results, 'imageBase64'),
                ])
            );

            if ($printResponse->getStatusCode() !== 200) {
                return $printResponse;
            }

            $printData = json_decode($printResponse->getContent(), true);

            RequestGeneratorImage::where('REQUISICAO_GERADOR_PLACAS', $results[0]['id'])
                ->update(['PATH_PDF' => public_path('img') . '/' . $printData['pdf']]);

            $pdfs[] = [
                'pdf'      => $printData['pdf'],
                'products' => array_column($chunkPayloads, 'product'),
            ];

            $allResults = array_merge($allResults, $results);
        }

        return response()->json([
            'status'      => 'success',
            'template_id' => $request->template_id,
            'products'    => $allResults,
            'type'        => $request->type,
            'pdf'         => $pdfs[0]['pdf'],
            'pdfs'        => $pdfs,
        ]);
    }

    public function print(Request $request): JsonResponse
    {

        if (!$request->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'O id é obrigatório',
            ], 422);
        }

        $products = RequestGeneratorImageProduct::where('REQUISICAO_GERADOR_PLACAS', $request->id)
            ->when($request->produto, fn($q) => $q->whereIn('PRODUTO', (array) $request->produto))
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nenhum produto encontrado',
            ], 404);
        }

        $images = $request->imageBase64;



        /**
         * type 1 = ETIQUETA
         * type 2 = PLACA
         *
         * */

        if ($request->type == 1) {
            $pdfPath = (new LabelService())->generate($images, 'print_' . $request->id);
        } elseif ($request->type == 2) {
            $pdfPath = (new PdfService())->generate($images, 'print_' . $request->id);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tipo de template inválido',
                'type'    => $request->type,
            ], 422);
        }



        return response()->json([
            'status' => 'success',
            'pdf'    => $pdfPath
        ]);
    }
}
