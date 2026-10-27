<?php

namespace App\Query;

use App\Enums\TypePages as TypePagesEnum;
use App\Models\DailyProducts;
use App\Models\RequestGeneratorImageProduct;
use Illuminate\Database\Eloquent\Builder;

class DailyProductsQuery
{
    /** $todos = true ignora os produtos já gerados e devolve todos os das lojas */
    public static function getDailyProducts(array $lojas, bool $todos = false)
    {
        $idsGerados = $todos ? [] : RequestGeneratorImageProduct::query()
            ->whereNotNull('ETIQUETA_PLACAS_RESULTADO_ID')
            ->distinct()
            ->pluck('ETIQUETA_PLACAS_RESULTADO_ID')
            ->flip()
            ->all();

        return self::vigentes()
            ->whereIn('loja', $lojas)
            ->get()
            /** Filtra em PHP: whereNotIn estoura o limite de 2100 parâmetros do SQL Server */
            ->reject(fn($item) => isset($idsGerados[$item->ID]))
            ->values()
            ->map(function ($item) {
                $item->TIPO_TEMPLATE = match (true) {
                    in_array((int) $item->ID_TEMPLATE, [91, 92, 93]) => 1,
                    in_array((int) $item->ID_TEMPLATE, [94, 95]) => 3,
                    default => 2,
                };

                /** Valor da tabela antes da troca abaixo, usado no Por_2 do Promoclube */
                $item->PRECO_PROMOCAO_ORIGINAL = $item->PRECO_PROMOCAO;

                $item->PRECO_PROMOCAO = TypePagesEnum::precoPromocao(
                    $item->PROCFIT_TIPO,
                    $item->PRECO_PROMOCAO,
                    $item->PRECO_VENDA,
                    $item->PRECO_MAXIMO,
                    $item->SUBTITULO_2,
                );

                if ($item->DATA_FINAL == null) {
                    $item->DATA_FINAL = $item->DATA_VALIDADE_PRODUTO;
                }

                return $item;
            });
    }

    /** Lojas com produtos vigentes hoje, para o products:recover sem --loja */
    public static function getStores(): array
    {
        return self::vigentes()
            ->distinct()
            ->orderBy('LOJA')
            ->pluck('LOJA')
            ->map(fn($loja): int => (int) $loja)
            ->all();
    }

    private static function vigentes(): Builder
    {
        return DailyProducts::query()
            ->whereNotNull('ID_TEMPLATE')
            ->whereNotNull('LOJA')
            ->where('PROCFIT_TIPO', '!=', TypePagesEnum::PRODUTOS_PV->value)
            /** Mesma regra da USP_PROMOCOES_CONSULTA_PRODUTO: promoção vigente hoje, exceto ETIQUETAS_GONDULA */
            ->where(function ($query) {
                $query->where('PROCFIT_TIPO', TypePagesEnum::ETIQUETAS_GONDULA->value)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('DATA_INICIAL')
                            ->whereNotNull('DATA_FINAL')
                            ->whereDate('DATA_INICIAL', '<=', today())
                            ->whereDate('DATA_FINAL', '>=', today());
                    });
            });
    }
}
