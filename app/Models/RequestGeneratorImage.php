<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestGeneratorImage extends Model
{
    protected $connection = 'sqlsrv_secondary';

    protected $table = 'REQUISICOES_GERADOR_PLACAS';

    protected $primaryKey = 'REQUISICAO_GERADOR_PLACAS';

    protected $fillable = [
        'TEMPLATE_ID',
        'REQUISICAO',
        'DATA_REQUISICAO',
        'HORA_REQUISICAO',
        'LOJA',
        'PATH_PDF',
        'TOTAL_PRODUTOS',
        'DOWNLOAD_REALIZADO'
    ];

    public $timestamps = false;

    public function products(): HasMany
    {
        return $this->hasMany(RequestGeneratorImageProduct::class, 'REQUISICAO_GERADOR_PLACAS');
    }

    public function paths(): HasMany
    {
        return $this->hasMany(RequestGeneretorPath::class, 'REQUISICAO_GERADOR_PLACAS');
    }
}
