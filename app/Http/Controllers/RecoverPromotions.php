<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TypePromotions;


class RecoverPromotions extends Controller
{

    public function __invoke()
    {

        $promotions = $this->getPromotions();


        return response()->json([
            'status' => 'success',
            'result'   => $promotions
        ]);
    }


    public function getPromotions()
    {

        $promotions = TypePromotions::getAllPromotions();

        return $promotions;
    }
}
