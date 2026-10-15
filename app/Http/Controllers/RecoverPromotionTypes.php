<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TypePages;

class RecoverPromotionTypes extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'status' => 'success',
            'result' => TypePages::options(),
        ]);
    }
}
