<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamiliaProduto extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'FAMILIAS_PRODUTOS';

    protected $primaryKey = 'FAMILIA_PRODUTO';

    public $timestamps = false;
}
