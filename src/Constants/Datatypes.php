<?php


namespace Larelastic\Elastic\Constants;


class Datatypes
{
    const KEYWORD = [
        'type' => 'keyword',
        'normalizer' => 'lowercase_normalizer'
    ];
    const BOOLEAN = [
        'type' => 'boolean',
    ];
    const TEXT = [
        'type' => 'text',
    ];
    const INTEGER = [
        'type' => 'integer',
    ];
    const FLOAT = [
        'type' => 'float',
    ];
    const DATE = [
        'type' => 'date',
        'format' => 'strict_date_optional_time||epoch_millis',
    ];
    const MONEY = [
        'type' => 'scaled_float',
        'scaling_factor' => 100,
    ];
}