<?php

declare(strict_types=1);

namespace App\Query;

use App\Models\DailyProducts;

class ClubProductsQuery
{
    /** IDs de ETIQUETA_PLACAS_RESULTADO (nameplate_label_printing) que são produtos do clube */
    public static function clubIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DailyProducts::query()
            ->whereIn('ID', $ids)
            ->where('PROCFIT_TIPO', 'PROMOCLUBE')
            ->pluck('ID')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
