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

    /** Texto da coluna "Tipo" na notificação enviada à loja, a partir do PROCFIT_TIPO */
    public static function notificacao(?string $procfitTipo): ?string
    {
        if ($procfitTipo === null) {
            return null;
        }

        return match ($procfitTipo) {
            self::ETIQUETAS_GONDULA->value => 'Alterações de Preço',
            self::TABELAS_ENCARTES_TABLOIDE->value => 'De/Por',
            'PROMOCLUBE' => 'PromoClube',
            self::PROMOCOES_AGRUPAMENTOS->value => 'Desconto % na Segunda Unidade',
            self::LEVEX_PAGUEY->value => 'Leve X Pague Y',
            self::PROMOCOES_FLEXIVEIS->value => 'Leve e Pague',
            default => self::tryFrom($procfitTipo)?->descricao() ?? $procfitTipo,
        };
    }

    /** Descrição da promoção a partir do PROCFIT_TIPO (substitui o CASE do SQL) */
    public static function descricaoPromocao(?string $procfitTipo, $precoPromocao): string
    {
        if ($procfitTipo === null) {
            return 'ETIQUETAS REDUZIDAS';
        }

        $tipo = self::tryFrom($procfitTipo);

        if (in_array($tipo, [self::PROMOCOES_FLEXIVEIS, self::PROMOCOES_AGRUPAMENTOS], true)) {
            if ($precoPromocao === null) {
                return $procfitTipo;
            }

            return (float) $precoPromocao == 0 ? 'LEVE X E PAGUE Y' : 'LEVE X E PAGUE ';
        }

        return match ($tipo) {
            self::LEVEX_PAGUEY => 'LEVE X E PAGUE Y',
            self::TABELAS_ENCARTES_TABLOIDE => 'ENCARTES',
            self::PRODUTOS_PV => 'PRODUTOS PV',
            self::ETIQUETAS_GONDULA => 'ETIQUETAS DE GONDULA',
            default => $procfitTipo,
        };
    }
}
