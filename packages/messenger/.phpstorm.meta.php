<?php namespace PHPSTORM_META {
    $STATIC_METHOD_TYPES = [
        \Symfony\Component\Messenger\Envelope::last('') => [
            "" == "@",
        ],
        \Symfony\Component\Messenger\Envelope::all('') => [
            "" == "@[]",
        ]
    ];
}