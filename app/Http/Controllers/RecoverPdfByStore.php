<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecoversPdfData;
use Illuminate\Http\Request;

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
        ]);

        return $this->recoverPdfByStoreResponse($request);
    }
}
