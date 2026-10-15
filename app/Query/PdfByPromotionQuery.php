<?php

declare(strict_types=1);

namespace App\Query;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PdfByPromotionQuery
{
    public const HORAS = 72;

    /** PDFs já gerados para a loja nas últimas 72 horas, opcionalmente filtrados pela promoção (PROCFIT_TIPO) */
    public static function getPdfByPromotion(Request $request): Collection
    {
        $query = PdfByStoreQuery::query($request)
            ->whereRaw(
                'CAST(RGP.DATA_REQUISICAO AS DATETIME) + CAST(RGP.HORA_REQUISICAO AS DATETIME) >= ?',
                [now()->subHours(self::HORAS)->format('Y-m-d\TH:i:s')],
            )
            ->whereNotNull('RGP.PATH_PDF');

        return PdfByStoreQuery::rows($query);
    }
}
