<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TypePages;
use App\Http\Controllers\Concerns\RecoversPdfData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecoverPdfByPromotion extends Controller
{
    use RecoversPdfData;

    public function __invoke(Request $request)
    {
        $request->validate([
            'store'    => 'required|integer',
            'promocao' => ['nullable', Rule::in(TypePages::codes())],
        ]);

        return $this->recoverPdfByPromotionResponse($request);
    }
}
