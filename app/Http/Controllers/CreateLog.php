<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Logs;

class CreateLog extends Controller
{

    public function __invoke(Request $request)
    {

        try {

            Logs::create([
                'DATA_EXECUCAO' => $request->data_execucao,
                'COMANDO_EXECUTADO' => $request->comando_executado
            ]);
            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }
}
