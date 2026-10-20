<?php

namespace App\Enums;

/** Origem da geração do PDF salva em ORIGEM_IMPRESSAO (varchar 10) */
enum PrintOrigin: string
{
    case PADRAO = 'PADRAO';
    case COMANDO = 'COMANDO';
}
