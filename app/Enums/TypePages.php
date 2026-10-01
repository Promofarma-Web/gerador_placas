<?php

namespace App\Enums;


enum TypePages: string
{

    case LEVEX_PAGUEY = 'LEVEX_PAGUEY';
    case PROMOCOES_FLEXIVEIS = 'PROMOCOES_FLEXIVEIS';
    case TABELAS_ENCARTES_TABLOIDE = 'TABELAS_ENCARTES_TABLOIDE';
    case PROMOCOES_AGRUPAMENTOS = 'PROMOCOES_AGRUPAMENTOS';
    case PRODUTOS_PV = 'PRODUTOS_PV';
    case ETIQUETAS_GONDULA = 'ETIQUETAS_GONDULA';


    public function descricao(): string
    {


        return match ($this) {

            self::LEVEX_PAGUEY => 'Leve X e Pague Y',
            self::PROMOCOES_FLEXIVEIS => 'Promoções Flexíveis',
            self::TABELAS_ENCARTES_TABLOIDE => 'Tabelas e Encartes Tabloide',
            self::PROMOCOES_AGRUPAMENTOS => 'Promoções Agrupamentos',
            self::PRODUTOS_PV => 'Produtos PV',
            self::ETIQUETAS_GONDULA => 'Etiquetas de Gondula',
        };
    }
}
