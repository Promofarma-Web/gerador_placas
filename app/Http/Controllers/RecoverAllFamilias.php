<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FamiliaProduto;
use Illuminate\Http\Request;

class RecoverAllFamilias extends Controller
{
    public function __invoke(Request $request)
    {
        $result = $this->recoverAllFamilias();

        return response()->json([
            'status' => 'success',
            'result' => $result,
        ]);
    }

    public function recoverAllFamilias()
    {
        return FamiliaProduto::query()
            ->select(['FAMILIA_PRODUTO', 'DESCRICAO'])
            ->get();
    }
}
