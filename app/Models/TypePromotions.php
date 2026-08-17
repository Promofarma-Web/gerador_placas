<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypePromotions extends Model
{

    protected $connection  = 'sqlsrv';

    protected $table = 'TIPOS_ETIQUETAS_PLACAS';

    protected $primaryKey = 'TIPO_ETIQUETA_PLACA';

    protected $fillable = [
        'TIPO_ETIQUETA_PLACA',
        'DESCRICAO',
        'ATIVO'
    ];

    public $timestamps = false;




    public static function getAllPromotions()
    {
        $mapa = [

            1 => 'Leve X e Pague Y',
            2 => 'Promoções De - Por',
            3 => 'Promoções Flexiveis',
            4 => 'Tabelas de Encartes - Tabloides',
            8 => 'Promoclube',
            9 => 'Descontos Progressivos',
            10 => 'Produtos PV',
            11 => 'Etiquetas de Gondola'
        ];


        return TypePromotions::whereIn('TIPO_ETIQUETA_PLACA', [1])->select('TIPO_ETIQUETA_PLACA', 'DESCRICAO', 'ATIVO')->get()->transform(function ($item) use ($mapa) {
            $item->DESCRICAO = $mapa[$item->TIPO_ETIQUETA_PLACA] ?? 'Desconhecido';
            return $item;
        });
    }

    public static function isValid(int $id): bool
    {
        $validIds = [1, 2, 3, 4, 8, 9, 10, 11];
        return in_array($id, $validIds) && self::where('TIPO_ETIQUETA_PLACA', $id)->exists();
    }
}
