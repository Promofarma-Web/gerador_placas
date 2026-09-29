<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RequestGeneretorPath extends Model
{
    protected $connection = 'sqlsrv_secondary';

    protected $table = 'REQUISICOES_GERADOR_PLACA_CAMINHOS';

    protected $primaryKey = 'REQUISICAO_GERADOR_PLACA_CAMINHO';

    protected $fillable = [
        'REQUISICAO_GERADOR_PLACAS',
        'CAMINHO',
    ];

    public $timestamps = false;

    public function request(): BelongsTo
    {
        return $this->belongsTo(RequestGeneratorImage::class, 'REQUISICAO_GERADOR_PLACAS');
    }
}
