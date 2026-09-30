<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Templates extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'mk_templates';

    protected $primaryKey = 'template_id';

    protected $fillable = [
        'TEMPLATE_ID',
        'TITULO',
        'IMAGEM_BASE',
        'ESTADO',
    ];

    public $timestamps = false;
}
