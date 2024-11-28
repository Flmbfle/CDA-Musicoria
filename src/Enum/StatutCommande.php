<?php

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDÉE = 'validee';
    case ANNULÉE = 'annulee';
    case LIVRÉE = 'livree';
}
