<?php


namespace Larelastic\Elastic\Constants;


class Normalizers
{
    const LOWERCASE = [
        'lowercase_normalizer' =>
            [
                'type' => 'custom',
                'char_filter' => [],
                'filter' => [
                    'lowercase'
                ]
            ]
    ];
}