<?php

declare(strict_types=1);

namespace App\Query;

use App\Models\RequestGeneratorImage;

class PendingDownloadQuery
{
    /** Requisições da loja com PDF ainda não baixado nos últimos dias (exceto hoje), no mesmo formato de registros da notificação */
    public static function getByStore(int $store): array
    {
        return RequestGeneratorImage::query()
            ->with('paths')
            ->where('LOJA', $store)
            ->whereNull('DOWNLOAD_REALIZADO')
            ->whereNotNull('PATH_PDF')
            ->where('DATA_REQUISICAO', '>=', today()->subDays(4)->toDateString())
            ->where('DATA_REQUISICAO', '<>', today()->toDateString())
            ->get()
            ->map(fn (RequestGeneratorImage $logger): array => [
                'paths' => $logger->paths->pluck('CAMINHO')->all(),
                'folhas' => NotificationPaperQuery::getPapersByRequest($logger),
            ])
            ->all();
    }
}
