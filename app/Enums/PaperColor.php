<?php

namespace App\Enums;

/** Cores de folha salvas em COR_PLANO_FUNDO (hexadecimal sem #) */
enum PaperColor: string
{
    case BRANCA = 'FFFFFF';
    case AMARELA = 'FFFF00';
    case ROSA = 'F7ADAF';

    public function label(): string
    {
        return match ($this) {
            self::BRANCA => 'Branca',
            self::AMARELA => 'Amarela',
            self::ROSA => 'Rosa',
        };
    }

    /** Texto da coluna "Tipo de Folha" na notificação: Folha Picotada Amarela */
    public static function paperFrom(?string $hex): ?string
    {
        $color = self::tryFrom(strtoupper((string) $hex));

        return $color ? 'Folha Picotada ' . $color->label() : null;
    }

    public static function labelFrom(?string $hex): string
    {
        return self::tryFrom(strtoupper((string) $hex))?->label() ?? (string) $hex;
    }
}
