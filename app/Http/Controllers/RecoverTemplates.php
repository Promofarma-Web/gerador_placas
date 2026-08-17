<?php

declare(strict_types=1);


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Templates;


class RecoverTemplates extends Controller
{
    public function __invoke(Request $request)
    {
        $templates = $this->getTemplates($request);
        return response()->json([
            'status' => 'success',
            'result'   => $templates
        ]);
    }
    public function getTemplates(Request $request)
    {
        $templates = Templates::query()
            ->when($request->filled('state'), function ($query) use ($request) {
                $query->where('ESTADO', $request->state);
            })
            ->when($request->filled('title'), function ($query) use ($request) {
                $query->where('TITULO', 'like', '%' . $request->title . '%');
            })
            ->select('TEMPLATE_ID', 'TITULO', 'ESTADO', 'IMAGEM_BASE')
            ->whereIn('TEMPLATE_ID', [87, 88, 89, 90, 91, 92, 93, 94, 95, 96])
            ->get();

        return $templates;
    }
}
