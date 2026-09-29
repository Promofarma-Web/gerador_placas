<?php

declare(strict_types=1);

namespace App\Services\Paper;

use App\Enums\Type;
use App\Services\Paper\Contracts\PaperInterface;

final class PaperFactory
{
    public static function make(Type $type): PaperInterface
    {
        return match ($type) {
            Type::Grande => app(PdfPaper::class),
            Type::Reduzida => app(LabelPaper::class),
            Type::Unica => app(TinyPaper::class),
        };
    }
}
