<?php

namespace App\Query;

use App\Models\DailyProducts;
use App\Enums\TypePages as TypePagesEnum;

class TypePages
{
    public static function getTipoFolha($loja)
    {
        return DailyProducts::query()
            ->select([
                'PROCFIT_TIPO',
                'PRECO_PROMOCAO',
                'TIPO_FOLHA',
                'COR_PLANO_FUNDO',
            ])
            ->where('LOJA', $loja)
            ->whereNotNull('DATA_INICIAL')
            ->whereNotNull('DATA_FINAL')
            ->whereDate('DATA_INICIAL', '<=', today())
            ->whereDate('DATA_FINAL', '>=', today())
            ->distinct()
            ->get()
            ->map(function ($item) {
                $tipo = TypePagesEnum::tryFrom($item->PROCFIT_TIPO);

                $item->PROCFIT_TIPO = match ($tipo) {
                    TypePagesEnum::LEVEX_PAGUEY => TypePagesEnum::LEVEX_PAGUEY->value,

                    TypePagesEnum::PROMOCOES_FLEXIVEIS,
                    TypePagesEnum::PROMOCOES_AGRUPAMENTOS => $item->PRECO_PROMOCAO == 0.00
                        ? TypePagesEnum::LEVEX_PAGUEY->value
                        : 'LEVE_PAGUE',

                    TypePagesEnum::TABELAS_ENCARTES_TABLOIDE => 'ENCARTES',

                    TypePagesEnum::PRODUTOS_PV => 'PV',

                    TypePagesEnum::ETIQUETAS_GONDULA => TypePagesEnum::ETIQUETAS_GONDULA->value,

                    default => $item->PROCFIT_TIPO,
                };

                $item->PROCFIT_TIPO_DESCRICAO = match ($item->PROCFIT_TIPO) {
                    TypePagesEnum::LEVEX_PAGUEY->value => 'LEVE X E PAGUE Y',
                    'LEVE_PAGUE' => 'LEVE X E PAGUE',
                    'ENCARTES' => 'ENCARTES',
                    'PV' => 'PRODUTOS PV',
                    TypePagesEnum::ETIQUETAS_GONDULA->value => 'ETIQUETAS DE GONDULA',
                    default => $item->PROCFIT_TIPO,
                };

                unset($item->PRECO_PROMOCAO);

                return $item;
            })
            ->unique(fn ($item) => $item->PROCFIT_TIPO . '|' . $item->TIPO_FOLHA . '|' . $item->COR_PLANO_FUNDO)
            ->values();
    }
}
