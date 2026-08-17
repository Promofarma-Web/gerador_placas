<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestGeneratorImageProduct extends Model
{
    protected $connection  = 'sqlsrv_secondary';

    protected $table = 'REQUISICOES_GERADOR_PLACAS_PRODUTOS';

    protected $primaryKey = 'REQUISICAO_GERADOR_PLACAS_IMAGEM';


    protected $fillable = [
        'REQUISICAO_GERADOR_PLACAS',
        'PRODUTO',
        'FAMILIA',
        'ETIQUETA_PLACAS_RESULTADO_ID'

    ];


    public $timestamps = false;

    public function tableMaster(): BelongsTo
    {
        return $this->belongsTo(RequestGeneratorImage::class, 'REQUISICAO_GERADOR_PLACAS', 'REQUISICAO_GERADOR_PLACAS');
    }

    public static function alreadyExists(array $data): bool
    {
        return static::where('PRODUTO', $data['PRODUTO'])
            ->whereHas(
                'tableMaster',
                fn($q) => $q
                    ->where('TEMPLATE_ID', $data['TEMPLATE_ID'])
                    ->where('LOJA', $data['LOJA'])
                    ->where('DATA_REQUISICAO', $data['DATA_REQUISICAO'])
            )
            ->exists();
    }
}
