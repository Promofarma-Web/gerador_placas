<?php

namespace App\Query;

use App\Models\DailyProducts;
use App\Models\RequestGeneratorImageProduct;

class DailyProductsQuery
{
    public static function getDailyProducts($loja)
    {
        $idsGerados = RequestGeneratorImageProduct::query()
            ->whereNotNull('ETIQUETA_PLACAS_RESULTADO_ID')
            ->pluck('ETIQUETA_PLACAS_RESULTADO_ID')
            ->toArray();

        return DailyProducts::query()
            ->whereNotNull('ID_TEMPLATE')
            ->whereNotNull('LOJA')
            ->whereNotIn('ID', $idsGerados)
            ->where('loja', $loja)
            ->get()
            ->map(function ($item) {
                $item->TIPO_TEMPLATE = match (true) {
                    in_array((int) $item->ID_TEMPLATE, [91, 92, 93]) => 1,
                    in_array((int) $item->ID_TEMPLATE, [94, 95]) => 3,
                    default => 2,
                };

                if ((float) $item->PRECO_PROMOCAO == 0) {
                    $item->PRECO_PROMOCAO = (string) $item->SUBTITULO_2;
                } else {
                    $item->PRECO_PROMOCAO = (string) $item->PRECO_PROMOCAO;
                }

                if ($item->DATA_FINAL == null) {
                    $item->DATA_FINAL = $item->DATA_VALIDADE_PRODUTO;
                }

                return $item;
            });
    }
}
