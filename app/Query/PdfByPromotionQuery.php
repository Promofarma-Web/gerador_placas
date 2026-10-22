<?php

declare(strict_types=1);

namespace App\Query;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PdfByPromotionQuery
{
    /** PDFs já gerados para a loja nas últimas 72 horas, com promoção vigente, opcionalmente filtrados pela promoção (PROCFIT_TIPO) */
    public static function getPdfByPromotion(Request $request): Collection
    {
        $query = PdfByStoreQuery::vigentes(PdfByStoreQuery::generatedInLastHours(PdfByStoreQuery::query($request)));

        return PdfByStoreQuery::rows($query);
    }
}
