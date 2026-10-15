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
                'TIPO_FOLHA',
                'COR_PLANO_FUNDO',
            ])
            ->where('LOJA', $loja)
            ->where(function ($query) {
                $query->where('PROCFIT_TIPO', TypePagesEnum::ETIQUETAS_GONDULA->value)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('DATA_INICIAL')
                            ->whereNotNull('DATA_FINAL')
                            ->whereDate('DATA_INICIAL', '<=', today())
                            ->whereDate('DATA_FINAL', '>=', today());
                    });
            })
            ->distinct()
            ->get()
            ->map(function ($item) {
                $item->PROCFIT_TIPO_DESCRICAO = TypePagesEnum::notificacao($item->PROCFIT_TIPO);

                return $item;
            })
            // Uma entrada por promoção (código do enum); TIPO_FOLHA vem digitado com variações de maiúsculas
            ->unique(fn ($item) => $item->PROCFIT_TIPO)
            ->values();
    }
}
