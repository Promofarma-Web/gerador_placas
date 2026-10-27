<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Query\TypePages;
use App\Query\DailyProductsQuery;

class DailyProducts extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'ETIQUETA_PLACAS_RESULTADO';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    public static function getDailyProducts(array $lojas, bool $todos = false)
    {
        return DailyProductsQuery::getDailyProducts($lojas, $todos);
    }

    public static function getStores(): array
    {
        return DailyProductsQuery::getStores();
    }

    public static function getTipoFolha($loja)
    {
        return  TypePages::getTipoFolha($loja);
    }
}
