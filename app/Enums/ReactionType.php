<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Reacciones con emoji disponibles para un libro (estilo Trakt TV).
 *
 * El valor persistido es el slug ASCII y no el emoji: con la colación
 * `utf8mb4_unicode_ci` (UCA 4.0.0) todos los caracteres suplementarios
 * (la mayoría de emojis) tienen el mismo peso, por lo que '🤯' = '🔥' a
 * efectos de comparación y colisionarían en la clave primaria compuesta.
 */
enum ReactionType: string
{
    case MindBlown = 'mind_blown';
    case ThumbsUp = 'thumbs_up';
    case Laugh = 'laugh';
    case Heart = 'heart';
    case Fire = 'fire';
    case Cry = 'cry';

    /**
     * Máximo de reacciones distintas que un usuario puede dejar en un libro.
     */
    public const int MAX_PER_USER = 3;

    public function emoji(): string
    {
        return match ($this) {
            self::MindBlown => '🤯',
            self::ThumbsUp => '👍',
            self::Laugh => '🤣',
            self::Heart => '❤️',
            self::Fire => '🔥',
            self::Cry => '😭',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
