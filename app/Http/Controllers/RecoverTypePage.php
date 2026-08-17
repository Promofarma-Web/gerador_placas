<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyProducts;
use Illuminate\Http\Request;


class RecoverTypePage extends Controller
{

    public function __invoke(Request $request)
    {

        $request->validate([
            'loja'    => 'required|integer',
        ]);

        $pages = $this->getTipoFolha($request->loja);


        return response()->json([
            'status' => 'success',
            'result'   => $pages
        ]);
    }


    public function getTipoFolha($loja)
    {

        $pages = DailyProducts::getTipoFolha($loja);

        return $pages;
    }
}
