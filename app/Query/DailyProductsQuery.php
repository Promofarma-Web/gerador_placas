<?php

namespace App\Query;

use App\Enums\TypePages as TypePagesEnum;
use App\Models\DailyProducts;
use App\Models\RequestGeneratorImageProduct;

class DailyProductsQuery
{
    /** $todos = true ignora os produtos já gerados e devolve todos os da loja */
    public static function getDailyProducts($loja, bool $todos = false)
    {
        $idsGerados = $todos ? [] : RequestGeneratorImageProduct::query()
            ->whereNotNull('ETIQUETA_PLACAS_RESULTADO_ID')
            ->pluck('ETIQUETA_PLACAS_RESULTADO_ID')
            ->toArray();



        return DailyProducts::query()
            ->whereNotNull('ID_TEMPLATE')
            ->whereNotNull('LOJA')
            ->when($idsGerados !== [], fn ($query) => $query->whereNotIn('ID', $idsGerados))
            ->where('loja', $loja)
            /** Mesma regra da USP_PROMOCOES_CONSULTA_PRODUTO: promoção vigente hoje, exceto ETIQUETAS_GONDULA */
            ->where(function ($query) {
                $query->where('PROCFIT_TIPO', TypePagesEnum::ETIQUETAS_GONDULA->value)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('DATA_INICIAL')
                            ->whereNotNull('DATA_FINAL')
                            ->whereDate('DATA_INICIAL', '<=', today())
                            ->whereDate('DATA_FINAL', '>=', today());
                    });
            })
            ->get()
            ->map(function ($item) {
                $item->TIPO_TEMPLATE = match (true) {
                    in_array((int) $item->ID_TEMPLATE, [91, 92, 93]) => 1,
                    in_array((int) $item->ID_TEMPLATE, [94, 95]) => 3,
                    default => 2,
                };

                $item->PRECO_PROMOCAO = TypePagesEnum::precoPromocao(
                    $item->PROCFIT_TIPO,
                    $item->PRECO_PROMOCAO,
                    $item->PRECO_VENDA,
                    $item->SUBTITULO_2,
                );

                if ($item->DATA_FINAL == null) {
                    $item->DATA_FINAL = $item->DATA_VALIDADE_PRODUTO;
                }

                return $item;
            });
    }
}
