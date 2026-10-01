<?php

declare(strict_types=1);

namespace App\Query;

use App\Enums\PaperColor as PaperColorEnum;
use App\Enums\TypePages as TypePagesEnum;
use App\Models\DailyProducts;
use App\Models\RequestGeneratorImage;
use Illuminate\Support\Collection;

class NotificationPaperQuery
{
    /** Tipo de folha, cor e promoção dos produtos da requisição (ETIQUETA_PLACAS_RESULTADO fica em outro banco, por isso duas consultas) */
    public static function getPapersByRequest(RequestGeneratorImage $logger): Collection
    {
        $ids = $logger->products()
            ->pluck('ETIQUETA_PLACAS_RESULTADO_ID')
            ->filter()
            ->unique();

        return DailyProducts::query()
            ->whereIn('ID', $ids)
            ->select([
                'TIPO_FOLHA',
                'COR_PLANO_FUNDO',
                'PROCFIT_TIPO',
                'PRECO_PROMOCAO',
            ])
            ->distinct()
            ->get()
            ->map(fn ($item) => [
                'tipo_folha' => $item->TIPO_FOLHA,
                'cor' => '#' . $item->COR_PLANO_FUNDO,
                'cor_nome' => PaperColorEnum::labelFrom($item->COR_PLANO_FUNDO),
                'promocao' => TypePagesEnum::descricaoPromocao($item->PROCFIT_TIPO, $item->PRECO_PROMOCAO),
            ])
            ->unique(fn ($item) => $item['tipo_folha'] . '|' . $item['cor'])
            ->values();
    }
}
