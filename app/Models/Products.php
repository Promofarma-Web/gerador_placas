<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Products extends Model
{
        protected $connection  = 'sqlsrv';

        protected $table = 'PRODUTOS';

        protected $primaryKey = 'PRODUTO';


        public $timestamps = false;

        public function familia(): BelongsTo
        {
                return $this->belongsTo(FamiliaProduto::class, 'FAMILIA_PRODUTO', 'FAMILIA_PRODUTO');
        }
}
