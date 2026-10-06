<?php

declare(strict_types=1);

namespace App\Query;

use App\Models\RequestGeneratorImage;

class DownloadPdfQuery
{
    public static function markAsDownloaded(string $filename): int
    {
        return RequestGeneratorImage::query()
            ->whereHas('paths', fn($query) => $query->where('CAMINHO', $filename))
            ->update(['DOWNLOAD_REALIZADO' => 'S']);
    }
}
