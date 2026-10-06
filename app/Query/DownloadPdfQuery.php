<?php

declare(strict_types=1);

namespace App\Query;

use App\Models\RequestGeneratorImage;

class DownloadPdfQuery
{
    /** Marca como baixada a requisição dona do arquivo (CAMINHO guarda só o nome do arquivo gerado) */
    public static function markAsDownloaded(string $filename): int
    {
        return RequestGeneratorImage::query()
            ->whereHas('paths', fn ($query) => $query->where('CAMINHO', $filename))
            ->update(['DOWNLOAD_REALIZADO' => 'S']);
    }
}
