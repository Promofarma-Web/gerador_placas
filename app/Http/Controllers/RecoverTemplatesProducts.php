<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecoverTemplatesProducts extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'produto' => 'integer',
            'loja'    => 'required|integer',
            'promocao' => 'string',
            'familia' => 'integer',
        ]);



        $result = DB::connection('sqlsrv')->select(
            'EXEC USP_PROMOCOES_CONSULTA_PRODUTO @PRODUTO = ?, @EMPRESA = ?, @PROMOCAO = ?, @FAMILIA = ?',
            [
                $request->produto,
                $request->loja,
                $request->promocao,
                $request->familia
            ]
        );

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }


    public function getProdutct($loja, $produto) {}
}
