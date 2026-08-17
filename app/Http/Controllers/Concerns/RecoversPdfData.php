<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\RequestGeneratorImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

trait RecoversPdfData
{
    protected function buildPdfQuery(Request $request): Builder
    {
        return RequestGeneratorImage::query()
            ->join('REQUISICOES_GERADOR_PLACAS_PRODUTOS as B', 'REQUISICOES_GERADOR_PLACAS.REQUISICAO_GERADOR_PLACAS', '=', 'B.REQUISICAO_GERADOR_PLACAS')
            ->join('PBS_PROMOFARMA_DADOS.dbo.ETIQUETA_PLACAS_RESULTADO as C', 'C.IMPRESSAO_ETIQUETA_PLACA', '=', 'B.ETIQUETA_PLACAS_RESULTADO_ID')
            ->leftjoin('PBS_PROMOFARMA_DADOS.dbo.FAMILIAS_PRODUTOS as F', 'F.FAMILIA_PRODUTO', '=', 'B.FAMILIA')
            ->whereRaw('CAST(GETDATE() AS DATE) BETWEEN C.DATA_INICIAL AND C.DATA_FINAL')
            ->where('REQUISICOES_GERADOR_PLACAS.LOJA', $request->store)
            ->whereBetween('REQUISICOES_GERADOR_PLACAS.DATA_REQUISICAO', [$request->requisition_initial_date, $request->requisition_final_date])
            ->when($request->filled('template'), function ($query) use ($request) {
                $query->where('REQUISICOES_GERADOR_PLACAS.TEMPLATE_ID', $request->template);
            })
            ->when($request->filled('product'), function ($query) use ($request) {
                $query->where('B.PRODUTO', $request->product);
            })
            ->when($request->filled('familia'), function ($query) use ($request) {
                $query->where('B.FAMILIA', $request->familia);
            })
            ->when($request->filled('promotion'), function ($query) use ($request) {
                $query->where('B.PROMOCAO', $request->promotion);
            })
            ->distinct()
            ->tap(fn(Builder $query) => $this->selectPdfColumns($query));
    }

    protected function buildPdfByStoreQuery(Request $request): Builder
    {
        return RequestGeneratorImage::query()
            ->from('REQUISICOES_GERADOR_PLACAS as RGP')
            ->join('REQUISICOES_GERADOR_PLACAS_PRODUTOS as B', 'RGP.REQUISICAO_GERADOR_PLACAS', '=', 'B.REQUISICAO_GERADOR_PLACAS')
            ->leftJoin('PBS_PROMOFARMA_DADOS.dbo.ETIQUETA_PLACAS_RESULTADO as C', 'C.ID', '=', 'B.ETIQUETA_PLACAS_RESULTADO_ID')
            ->leftJoin('PBS_PROMOFARMA_DADOS.dbo.PRODUTOS as D', 'B.PRODUTO', '=', 'D.PRODUTO')
            ->leftJoin('PBS_PROMOFARMA_DADOS.dbo.FAMILIAS_PRODUTOS as F', 'F.FAMILIA_PRODUTO', '=', 'B.FAMILIA')
            ->whereRaw('(B.ETIQUETA_PLACAS_RESULTADO_ID IS NULL OR CAST(GETDATE() AS DATE) BETWEEN C.DATA_INICIAL AND C.DATA_FINAL)')
            ->where('RGP.LOJA', $request->store)
            ->when($request->filled('template'), function ($query) use ($request) {
                $query->where('RGP.TEMPLATE_ID', $request->template);
            })
            ->when($request->filled('produto'), function ($query) use ($request) {
                $query->where('B.PRODUTO', $request->produto);
            })
            ->when($request->filled('familia'), function ($query) use ($request) {
                $query->where('B.FAMILIA', $request->familia);
            })
            ->select([
                'RGP.REQUISICAO_GERADOR_PLACAS',
                'RGP.TEMPLATE_ID',
                'RGP.REQUISICAO',
                'RGP.DATA_REQUISICAO',
                'RGP.HORA_REQUISICAO',
                'RGP.PATH_PDF',
                'RGP.LOJA',
                'B.PRODUTO',
                'D.DESCRICAO_REDUZIDA',
                'B.FAMILIA as FAMILIA_PRODUTO',
                'F.DESCRICAO as FAMILIA_DESCRICAO',
            ])
            ->selectRaw("
                                CASE
                                    WHEN PROCFIT_TIPO = 'LEVEX_PAGUEY' THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'TABELAS_ENCARTES_TABLOIDE' THEN 'ENCARTES'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'PRODUTOS_PV' THEN 'PRODUTOS PV'
                                    WHEN PROCFIT_TIPO = 'ETIQUETAS_GONDULA' THEN 'ETIQUETAS DE GONDULA'
                                    ELSE PROCFIT_TIPO
                                END AS DESCRICAO_PROMOCAO
    ");
    }

    private function groupPdfRowsByProduct(Collection $rows): Collection
    {
        return $rows
            ->groupBy('REQUISICAO_GERADOR_PLACAS')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'REQUISICAO_GERADOR_PLACAS' => $first->REQUISICAO_GERADOR_PLACAS,
                    'TEMPLATE_ID'               => $first->TEMPLATE_ID,
                    'REQUISICAO'                => $first->REQUISICAO,
                    'DATA_REQUISICAO'           => $first->DATA_REQUISICAO,
                    'HORA_REQUISICAO'           => $first->HORA_REQUISICAO,
                    'PATH_PDF'                  => $first->PATH_PDF,
                    'LOJA'                      => $first->LOJA,
                    'DESCRICAO_PROMOCAO'        => $first->DESCRICAO_PROMOCAO,
                    'PRODUTOS'                  => $group
                        ->filter(fn($row) => (int) $row->PRODUTO !== 0)
                        ->unique(fn($row) => $row->PRODUTO . '|' . $row->DESCRICAO_REDUZIDA)
                        ->map(function ($row) {
                            return [
                                'PRODUTO'            => $row->PRODUTO,
                                'DESCRICAO_REDUZIDA' => $row->DESCRICAO_REDUZIDA,
                            ];
                        })->values(),
                    'FAMILIA'                   => $group
                        ->filter(fn($row) => (int) $row->FAMILIA_PRODUTO !== 0)
                        ->unique(fn($row) => $row->FAMILIA_PRODUTO . '|' . $row->FAMILIA_DESCRICAO)
                        ->map(function ($row) {
                            return [
                                'FAMILIA_PRODUTO' => $row->FAMILIA_PRODUTO,
                                'DESCRICAO'        => $row->FAMILIA_DESCRICAO,
                            ];
                        })->values(),
                ];
            })
            ->values();
    }

    private function selectPdfColumns(Builder $query): Builder
    {
        return $query
            ->select([
                'REQUISICOES_GERADOR_PLACAS.REQUISICAO_GERADOR_PLACAS',
                'REQUISICOES_GERADOR_PLACAS.TEMPLATE_ID',
                'REQUISICOES_GERADOR_PLACAS.REQUISICAO',
                'REQUISICOES_GERADOR_PLACAS.DATA_REQUISICAO',
                'REQUISICOES_GERADOR_PLACAS.HORA_REQUISICAO',
                'REQUISICOES_GERADOR_PLACAS.PATH_PDF',
                'REQUISICOES_GERADOR_PLACAS.LOJA',
                'F.DESCRICAO AS FAMILIA',
            ])
            ->selectRaw("
                                CASE
                                    WHEN PROCFIT_TIPO = 'LEVEX_PAGUEY' THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'TABELAS_ENCARTES_TABLOIDE' THEN 'ENCARTES'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'PRODUTOS_PV' THEN 'PRODUTOS PV'
                                    WHEN PROCFIT_TIPO = 'ETIQUETAS_GONDULA' THEN 'ETIQUETAS DE GONDULA'
                                    ELSE PROCFIT_TIPO
                                END AS DESCRICAO_PROMOCAO
    ");
    }

    protected function recoverPdfResponse(Request $request): JsonResponse
    {
        $result = $this->buildPdfQuery($request)->get();

        if ($result->isEmpty()) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Nenhum template encontrado para os parâmetros informados.',
                'result'  => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'result' => $result,
        ]);
    }

    protected function recoverPdfByStoreResponse(Request $request): JsonResponse
    {
        $rows = $this->buildPdfByStoreQuery($request)->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Nenhum PDF encontrado para os parâmetros informados.',
                'result'  => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'result' => $this->groupPdfRowsByProduct($rows),
        ]);
    }
}
