<?php

namespace App\Enums;

enum ColorRules: string
{
    case TIPO = '1';
    case TEMPLATE_ID = '88';

    public static function getLabel(string $type, int|string $templateId): array
    {
        if ($type === self::TIPO->value) {
            return [
                'color' => '#000000',
                'background-color' => '#ffffff',
                'title' => 'Branca',
            ];
        }

        if ($templateId === self::TEMPLATE_ID->value) {
            return [
                'color' => '#000000',
                'background-color' => '#ff69b4',
                'title' => 'Rosa',
            ];
        }

        return [
            'color' => '#000000',
            'background-color' => '#ffff00',
            'title' => 'Amarela',
        ];
    }
}
