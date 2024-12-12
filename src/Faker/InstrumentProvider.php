<?php

namespace App\Faker;

use Faker\Provider\Base as BaseProvider;

class InstrumentProvider extends BaseProvider
{
    private static $instruments = [
        'Guitar',
        'Piano',
        'Violin',
        'Drum',
        'Flute',
        'Saxophone',
        'Trumpet',
        'Cello',
        'Harp',
        'Clarinet',
        'Oboe',
    ];

    public function instrument(): string
    {
        return self::randomElement(self::$instruments);
    }
}
