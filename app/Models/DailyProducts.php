<?php

namespace App\Models;

use App\Models\Logs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\RequestGeneratorImageProduct;

class DailyProducts extends Model

{
    protected $connection  = 'sqlsrv';

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
            ->where('ID_TEMPLATE', 93)
            ->where('PRODUTO', 1133701)
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
        return self::query()
            ->select(
                DB::RAW("CASE
                            WHEN PROCFIT_TIPO = 'LEVEX_PAGUEY' THEN 'LEVEX_PAGUEY'
                            WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVEX_PAGUEY'
                            WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE_PAGUE'
                            WHEN PROCFIT_TIPO = 'TABELAS_ENCARTES_TABLOIDE' THEN 'ENCARTES'
                            WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVEX_PAGUEY'
                            WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE_PAGUE'
                            WHEN PROCFIT_TIPO = 'PRODUTOS_PV' THEN 'PV'
                            WHEN PROCFIT_TIPO = 'ETIQUETAS_GONDULA' THEN 'ETIQUETAS_GONDULA'
                            ELSE PROCFIT_TIPO
                        END AS PROCFIT_TIPO"),
                DB::RAW(" CASE
                                    WHEN PROCFIT_TIPO = 'LEVEX_PAGUEY' THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_FLEXIVEIS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'TABELAS_ENCARTES_TABLOIDE' THEN 'ENCARTES'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO = 0.00 THEN 'LEVE X E PAGUE Y'
                                    WHEN PROCFIT_TIPO = 'PROMOCOES_AGRUPAMENTOS' AND PRECO_PROMOCAO <> 0.00 THEN 'LEVE X E PAGUE '
                                    WHEN PROCFIT_TIPO = 'PRODUTOS_PV' THEN 'PRODUTOS PV'
                                    WHEN PROCFIT_TIPO = 'ETIQUETAS_GONDULA' THEN 'ETIQUETAS DE GONDULA'
                                    ELSE PROCFIT_TIPO
                                END AS PROCFIT_TIPO_DESCRICAO"),
                'TIPO_FOLHA',
                'COR_PLANO_FUNDO'

            )->where('LOJA', $loja)
            ->distinct()->get();
    }
}
