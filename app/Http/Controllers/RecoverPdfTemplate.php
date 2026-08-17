<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecoversPdfData;
use Illuminate\Http\Request;

class RecoverPdfTemplate extends Controller
{
    use RecoversPdfData;

    public function __invoke(Request $request)
    {
        return $this->recoverPdfResponse($request);
    }

    public function recoverPdfTemplate(Request $request)
    {
        return $this->buildPdfQuery($request)->get();
    }
}
