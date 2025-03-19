<?php

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'En attente';
    case VALIDEE = 'Validé';
    case ANNULE = 'Annulé';
    case ENVOYE = 'Envoyé';
    case LIVREE = 'Livré';

    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validé',
            self::ANNULE => 'Annulé',
            self::ENVOYE => 'Envoyé',
            self::LIVREE => 'Livré',
        };
    }
}
