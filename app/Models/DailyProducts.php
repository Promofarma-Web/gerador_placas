<?php

namespace App\Models;

use App\Models\Logs;
use App\Models\RequestGeneratorImageProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Query\TypePages;

class DailyProducts extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'ETIQUETA_PLACAS_RESULTADO';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    public static function getDailyProducts($loja)
    {
        $idsGerados = RequestGeneratorImageProduct::query()
            ->whereNotNull('ETIQUETA_PLACAS_RESULTADO_ID')
            ->pluck('ETIQUETA_PLACAS_RESULTADO_ID')
            ->toArray();

        $products = DailyProducts::query()
            ->whereNotNull('ID_TEMPLATE')
            ->whereNotNull('LOJA')
            ->whereNotIn('ID', $idsGerados)
            ->where('loja', $loja)
            ->distinct()
            ->get()
            ->map(function ($item) {
                $item->TIPO_TEMPLATE = in_array($item->ID_TEMPLATE, [95, 94, 93, 92, 91]) ? 1 : 2;

                if ((float) $item->PRECO_PROMOCAO == 0) {
                    $item->PRECO_PROMOCAO = (string) $item->SUBTITULO_2;
                } else {
                    $item->PRECO_PROMOCAO = (string) $item->PRECO_PROMOCAO;
                }

                if ($item->DATA_FINAL == null) {
                    $item->DATA_FINAL = $item->DATA_VALIDADE_PRODUTO;
                }

                return $item;
            });

        return $products;
    }

    public static function getTipoFolha($loja)
    {
        return  TypePages::getTipoFolha($loja);
    }
}
