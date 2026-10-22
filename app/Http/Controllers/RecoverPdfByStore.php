<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PrintOrigin;
use App\Enums\TypePages;
use App\Http\Controllers\Concerns\RecoversPdfData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecoverPdfByStore extends Controller
{
    use RecoversPdfData;

    public function __invoke(Request $request)
    {
        $request->validate([
            'store'    => 'required|integer',
            'template' => 'nullable|integer',
            'produto'  => 'nullable|integer',
            'familia'  => 'nullable|integer',
            'promocao' => ['nullable', Rule::in(TypePages::codes())],
            'origem'   => ['nullable', Rule::enum(PrintOrigin::class)],
        ]);

        return $this->recoverPdfByStoreResponse($request);
    }
}
