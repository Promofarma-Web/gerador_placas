<?php

declare(strict_types=1);

namespace App\Services\Paper\Contracts;

interface PaperInterface
{
    public function generate(array $base64Images, string $filename): string;
}
